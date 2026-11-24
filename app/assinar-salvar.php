<?php
// ============================================================
// PROCESSAMENTO DO AUTOCADASTRO
// Cria o tenant (estabelecimento), o subdominio e a assinatura
// mensal no Asaas. O acesso e liberado quando o webhook confirma
// o primeiro pagamento.
// ============================================================
require_once(__DIR__ . '/superadmin/_auth.php');
require_once(__DIR__ . '/superadmin/_asaas.php');
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assinar.php');
    exit;
}

$csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
if (empty($_SESSION['assinar_csrf']) || !hash_equals($_SESSION['assinar_csrf'], $csrf)) {
    header('Location: assinar.php?erro=' . urlencode('Sessão expirada. Envie o formulário novamente.'));
    exit;
}

$nome      = trim((string) @$_POST['nome']);
$slug      = sa_slug(@$_POST['slug']);
$email     = trim((string) @$_POST['email']);
$telefone  = trim((string) @$_POST['telefone']);
$senha     = (string) @$_POST['senha'];
$senha2    = (string) @$_POST['senha2'];
$plano_id  = (int) @$_POST['plano_id'];
$forma     = (string) @$_POST['forma'];
$cpf_cnpj  = preg_replace('/\D+/', '', (string) @$_POST['cpf_cnpj']);

$formas_validas = array('UNDEFINED', 'PIX', 'BOLETO');
if (!in_array($forma, $formas_validas, true)) {
    $forma = 'UNDEFINED';
}

$voltar = function ($mensagem) use ($nome, $slug, $email, $telefone, $cpf_cnpj) {
    $params = http_build_query(array(
        'erro'      => $mensagem,
        'nome'      => $nome,
        'slug'      => $slug,
        'email'     => $email,
        'telefone'  => $telefone,
        'cpf_cnpj'  => $cpf_cnpj,
    ));
    header('Location: assinar.php?' . $params);
    exit;
};

if ($nome === '' || mb_strlen($nome) < 3) {
    $voltar('Informe o nome do estabelecimento.');
}
if ($slug === '' || strlen($slug) < 3) {
    $voltar('O endereço do site precisa ter ao menos 3 caracteres.');
}

$reservados = array('www', 'mail', 'smtp', 'ftp', 'api', 'app', 'admin', 'superadmin', 'sistema', 'painel', 'assinar', 'dev', 'teste', 'suporte', 'status', 'blog', 'loja');
if (in_array($slug, $reservados, true)) {
    $voltar('Este endereço é reservado. Escolha outro.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $voltar('Informe um e-mail válido.');
}
if (strlen($senha) < 6) {
    $voltar('A senha precisa ter ao menos 6 caracteres.');
}
if ($senha !== $senha2) {
    $voltar('As senhas não conferem.');
}
// Plano escolhido
$q = $pdo->prepare("SELECT * FROM saas_planos WHERE id = :id AND ativo = 'Sim' LIMIT 1");
$q->bindValue(':id', $plano_id, PDO::PARAM_INT);
$q->execute();
$plano = $q->fetch(PDO::FETCH_ASSOC);
if (!$plano) {
    $voltar('Escolha um plano válido.');
}
$valor = (float) $plano['valor'];
$gratis = ($valor <= 0);

// O Asaas exige CPF/CNPJ do cliente para emitir a cobranca
if ($cpf_cnpj !== '' && !in_array(strlen($cpf_cnpj), array(11, 14), true)) {
    $voltar('Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.');
}
if (!$gratis && $cpf_cnpj === '') {
    $voltar('Informe o CPF ou CNPJ para emitir a cobrança do plano pago.');
}

// Plano gratuito nao passa pelo Asaas
if (!$gratis && !asaas_configurado()) {
    $voltar('Pagamento indisponível no momento. Tente novamente mais tarde.');
}

// Unicidade de slug e e-mail
$q = $pdo->prepare("SELECT id FROM tenants WHERE slug = :slug LIMIT 1");
$q->bindValue(':slug', $slug);
$q->execute();
if ($q->fetchColumn()) {
    $voltar('Este endereço já está em uso. Escolha outro.');
}

$q = $pdo->prepare("SELECT id FROM tenants WHERE email = :email LIMIT 1");
$q->bindValue(':email', $email);
$q->execute();
if ($q->fetchColumn()) {
    $voltar('Já existe uma conta com este e-mail. Fale com o suporte.');
}

$token = bin2hex(random_bytes(24));

// Cria o estabelecimento aguardando pagamento
$ins = $pdo->prepare("INSERT INTO tenants
    (slug, nome, email, telefone, cpf_cnpj, plano_id, status, vencimento, senha_crip, token, origem, criado_em)
    VALUES (:slug, :nome, :email, :telefone, :cpf, :plano, :status, NULL, :senha, :token, 'autocadastro', NOW())");
$ins->bindValue(':status', $gratis ? 'trial' : 'pendente');
$ins->bindValue(':cpf', $cpf_cnpj !== '' ? $cpf_cnpj : null, $cpf_cnpj !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
$ins->bindValue(':slug', $slug);
$ins->bindValue(':nome', $nome);
$ins->bindValue(':email', $email);
$ins->bindValue(':telefone', $telefone);
$ins->bindValue(':plano', $plano_id, PDO::PARAM_INT);
$ins->bindValue(':senha', bb_hash_senha($senha));
$ins->bindValue(':token', $token);
$ins->execute();

$tenant_id = (int) $pdo->lastInsertId();

// Subdominio do cliente
$hostname = $slug . '.' . SA_DOMINIO_BASE;
$dom = $pdo->prepare("INSERT INTO tenant_dominios (hostname, tenant_id, tipo, verificado_em)
    VALUES (:host, :id, 'subdominio', NOW())
    ON DUPLICATE KEY UPDATE tenant_id = :id2");
$dom->bindValue(':host', $hostname);
$dom->bindValue(':id', $tenant_id, PDO::PARAM_INT);
$dom->bindValue(':id2', $tenant_id, PDO::PARAM_INT);
$dom->execute();

// Prepara o banco de dados do estabelecimento (idempotente).
// O login no painel so e liberado quando o webhook confirmar o pagamento.
try {
    bb_tenant_provisionar($pdo, $tenant_id);
} catch (Exception $e) {
    // Mantem o cadastro; o pagamento/ativacao segue normalmente
}

// Plano gratuito: conta ja nasce ativa (status trial), sem cobranca
if ($gratis) {
    unset($_SESSION['assinar_csrf']);
    header('Location: assinar-pagamento.php?t=' . urlencode($token));
    exit;
}

// Assinatura recorrente no Asaas
$q = $pdo->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
$q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
$q->execute();
$tenant = $q->fetch(PDO::FETCH_ASSOC);

$assinatura = asaas_criar_assinatura($pdo, $tenant, $valor, $forma, date('Y-m-d'));

if (!$assinatura['ok']) {
    // Mantem o cadastro para o super admin resolver, mas avisa o cliente
    $voltar('Cadastro criado, mas o pagamento não pôde ser gerado: ' . $assinatura['erro'] . ' Fale com o suporte informando seu e-mail.');
}

unset($_SESSION['assinar_csrf']);
header('Location: assinar-pagamento.php?t=' . urlencode($token));
exit;
