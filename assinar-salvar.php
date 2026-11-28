<?php
// ============================================================
// PROCESSAMENTO DO AUTOCADASTRO
// Cria o tenant (estabelecimento), o subdominio e a assinatura
// mensal no Asaas. O acesso e liberado quando o pagamento e
// confirmado (webhook) ou quando a tela de pagamento encontra a
// cobranca paga na API do Asaas.
// ============================================================
require_once(__DIR__ . '/superadmin/_auth.php');
require_once(__DIR__ . '/superadmin/_asaas.php');
require_once(__DIR__ . '/sistema/email.php');
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);
bb_tenant_garantir_colunas($pdo);

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
$segmento  = (isset($_POST['segmento']) && $_POST['segmento'] === 'salao') ? 'salao' : 'barbearia';

$formas_validas = array('UNDEFINED', 'PIX', 'BOLETO');
if (!in_array($forma, $formas_validas, true)) {
    $forma = 'UNDEFINED';
}

$voltar = function ($mensagem) use ($nome, $slug, $email, $telefone, $cpf_cnpj, $segmento) {
    $params = http_build_query(array(
        'erro'      => $mensagem,
        'nome'      => $nome,
        'slug'      => $slug,
        'email'     => $email,
        'telefone'  => $telefone,
        'cpf_cnpj'  => $cpf_cnpj,
        'segmento'  => $segmento,
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

// ------------------------------------------------------------
// Retomada do cadastro
// Se a cobranca falhou (Asaas fora do ar, por exemplo) o cadastro
// ficou 'pendente' sem assinatura. Comprar de novo com o mesmo
// e-mail nao pode ser barrado: o cadastro pendente e atualizado.
// ------------------------------------------------------------
$retomar = null;
$q = $pdo->prepare("SELECT * FROM tenants WHERE LOWER(TRIM(email)) = LOWER(:email) LIMIT 1");
$q->bindValue(':email', $email);
$q->execute();
$existente = $q->fetch(PDO::FETCH_ASSOC);
if ($existente) {
    $status_ex  = strtolower(trim((string) $existente['status']));
    $origem_ex  = strtolower(trim((string) $existente['origem']));
    $sem_cobranca = empty($existente['asaas_subscription_id']);
    if ($status_ex === 'pendente' && $origem_ex === 'autocadastro' && $sem_cobranca) {
        $retomar = $existente;
    } else {
        $voltar('Já existe uma conta com este e-mail. Fale com o suporte.');
    }
}
$tenant_id_existente = $retomar ? (int) $retomar['id'] : 0;

// Rotulo DNS valido, nome nao reservado e sem colisao com cliente/host existente
$motivo_slug = bb_slug_motivo($pdo, $slug, $tenant_id_existente);
if ($motivo_slug !== '') {
    $voltar($motivo_slug);
}

$token = $retomar ? trim((string) $retomar['token']) : '';
if ($token === '') {
    $token = bin2hex(random_bytes(24));
}
$senha_hash = bb_hash_senha($senha);

if ($retomar) {
    $q = $pdo->prepare("UPDATE tenants
        SET slug = :slug, nome = :nome, telefone = :telefone, cpf_cnpj = :cpf, segmento = :segmento,
            plano_id = :plano, status = :status, senha_crip = :senha, token = :token
        WHERE id = :id");
    $q->bindValue(':id', $tenant_id_existente, PDO::PARAM_INT);
    $tenant_id = $tenant_id_existente;
} else {
    $q = $pdo->prepare("INSERT INTO tenants
        (slug, nome, email, telefone, cpf_cnpj, segmento, plano_id, status, vencimento, senha_crip, token, origem, criado_em)
        VALUES (:slug, :nome, :email, :telefone, :cpf, :segmento, :plano, :status, NULL, :senha, :token, 'autocadastro', NOW())");
    $q->bindValue(':email', $email);
    $tenant_id = 0;
}
$q->bindValue(':status', $gratis ? 'trial' : 'pendente');
$q->bindValue(':cpf', $cpf_cnpj !== '' ? $cpf_cnpj : null, $cpf_cnpj !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
$q->bindValue(':slug', $slug);
$q->bindValue(':nome', $nome);
$q->bindValue(':telefone', $telefone);
$q->bindValue(':plano', $plano_id, PDO::PARAM_INT);
$q->bindValue(':senha', $senha_hash);
$q->bindValue(':token', $token);
$q->bindValue(':segmento', $segmento);
try {
    $q->execute();
} catch (PDOException $e) {
    // Corrida: outra pessoa registrou o mesmo slug entre a validacao e o INSERT
    if ((string) $e->getCode() === '23000') {
        $voltar('Este endereço acabou de ser registrado por outra pessoa. Escolha outro.');
    }
    $voltar('Não foi possível concluir o cadastro agora. Tente novamente.');
}

if ($tenant_id === 0) {
    $tenant_id = (int) $pdo->lastInsertId();
}

// Subdominio do cliente
$hostname = $slug . '.' . SA_DOMINIO_BASE;
// Troca de endereco na retomada: o subdominio antigo deste cliente sai
$q = $pdo->prepare("DELETE FROM tenant_dominios WHERE tenant_id = :id AND tipo = 'subdominio' AND hostname <> :host");
$q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
$q->bindValue(':host', $hostname);
$q->execute();
// Nunca "rouba" um hostname existente: o UPDATE so mexe no tipo, e apenas
// quando a linha ja e deste mesmo estabelecimento.
$dom = $pdo->prepare("INSERT INTO tenant_dominios (hostname, tenant_id, tipo, verificado_em)
    VALUES (:host, :id, 'subdominio', NOW())
    ON DUPLICATE KEY UPDATE tipo = IF(tenant_id = VALUES(tenant_id), 'subdominio', tipo)");
$dom->bindValue(':host', $hostname);
$dom->bindValue(':id', $tenant_id, PDO::PARAM_INT);
$dom->execute();

// Prepara o banco de dados do estabelecimento (idempotente).
// O login no painel so e liberado quando o pagamento for confirmado.
try {
    bb_tenant_provisionar($pdo, $tenant_id);
} catch (Exception $e) {
    // Mantem o cadastro; o pagamento/ativacao segue normalmente
}

// A senha do administrador vale no banco do cliente (nao so em tenants)
try {
    bb_tenant_sincronizar_admin($pdo, $tenant_id, $senha_hash, $email);
} catch (Exception $e) {
    // segue: o provisionamento do pagamento ajusta o administrador
}

// Plano gratuito: conta ja nasce ativa (status trial), sem cobranca
if ($gratis) {
    try {
        bb_boas_vindas_enviar($pdo, $tenant_id);
    } catch (Exception $e) {
        // o acesso continua valido sem o e-mail
    }
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
    $voltar('Cadastro criado, mas o pagamento não pôde ser gerado: ' . $assinatura['erro'] . ' Tente novamente em alguns minutos ou fale com o suporte.');
}

unset($_SESSION['assinar_csrf']);
header('Location: assinar-pagamento.php?t=' . urlencode($token));
exit;
