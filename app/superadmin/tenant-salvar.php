<?php
require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/_asaas.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);
bb_tenant_garantir_colunas($pdo);

$id         = (int) @$_POST['id'];
$nome       = trim((string) @$_POST['nome']);
$slug       = sa_slug(@$_POST['slug']);
$email      = trim((string) @$_POST['email']);
$telefone   = trim((string) @$_POST['telefone']);
$plano_id   = (int) @$_POST['plano_id'];
$status     = (string) @$_POST['status'];
$vencimento = trim((string) @$_POST['vencimento']);
$senha      = (string) @$_POST['senha'];

if ($nome === '' or $slug === '') {
    header('Location: tenants.php?erro=' . urlencode('nome e subdomínio são obrigatórios'));
    exit;
}

if ($id == 0 && $senha === '') {
    header('Location: tenants.php?erro=' . urlencode('defina a senha do administrador do novo estabelecimento'));
    exit;
}

if (!in_array($status, array('trial', 'ativo', 'suspenso', 'cancelado', 'pendente'))) {
    $status = 'trial';
}

if ($senha !== '' && strlen($senha) < 6) {
    header('Location: tenants.php?erro=' . urlencode('a senha do administrador precisa ter ao menos 6 caracteres') . '&id=' . $id);
    exit;
}

// Slug unico
$q = $pdo->prepare("SELECT id FROM tenants WHERE slug = :slug AND id <> :id LIMIT 1");
$q->bindValue(':slug', $slug);
$q->bindValue(':id', $id);
$q->execute();
if ($q->fetch()) {
    header('Location: tenants.php?erro=' . urlencode('este subdomínio já está em uso'));
    exit;
}

$plano_id = $plano_id > 0 ? $plano_id : null;
$vencimento = ($vencimento !== '') ? $vencimento : null;

if ($id > 0) {
    $q = $pdo->prepare("UPDATE tenants SET nome = :nome, slug = :slug, email = :email, telefone = :telefone,
                        plano_id = :plano, status = :status, vencimento = :venc WHERE id = :id");
    $q->bindValue(':id', $id);
} else {
    $q = $pdo->prepare("INSERT INTO tenants (nome, slug, email, telefone, plano_id, status, vencimento, senha_crip, origem, criado_em)
                        VALUES (:nome, :slug, :email, :telefone, :plano, :status, :venc, :senha, 'manual', NOW())");
    $q->bindValue(':senha', $senha !== '' ? bb_hash_senha($senha) : '');
}

$q->bindValue(':nome', $nome);
$q->bindValue(':slug', $slug);
$q->bindValue(':email', $email);
$q->bindValue(':telefone', $telefone);
$q->bindValue(':plano', $plano_id, $plano_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
$q->bindValue(':status', $status);
$q->bindValue(':venc', $vencimento, $vencimento ? PDO::PARAM_STR : PDO::PARAM_NULL);
$q->execute();

if ($id == 0) {
    $id = (int) $pdo->lastInsertId();
} elseif ($senha !== '') {
    $q = $pdo->prepare("UPDATE tenants SET senha_crip = :senha WHERE id = :id");
    $q->bindValue(':senha', bb_hash_senha($senha));
    $q->bindValue(':id', $id, PDO::PARAM_INT);
    $q->execute();
}

// Dominio do subdominio
$hostname = $slug . '.' . SA_DOMINIO_BASE;
$pdo->prepare("DELETE FROM tenant_dominios WHERE tenant_id = :id AND tipo = 'subdominio'")
    ->execute(array(':id' => $id));

$q = $pdo->prepare("INSERT INTO tenant_dominios (hostname, tenant_id, tipo, verificado_em)
                    VALUES (:host, :id, 'subdominio', NOW())
                    ON DUPLICATE KEY UPDATE tenant_id = :id2, tipo = 'subdominio'");
$q->bindValue(':host', $hostname);
$q->bindValue(':id', $id);
$q->bindValue(':id2', $id);
$q->execute();

// Cria/atualiza o banco de dados do estabelecimento (idempotente)
$provisionamento = bb_tenant_provisionar($pdo, $id);
if (!$provisionamento['ok']) {
    header('Location: tenants.php?erro=' . urlencode('estabelecimento salvo, mas o banco não foi criado: ' . $provisionamento['erro']) . '&id=' . $id);
    exit;
}

header('Location: tenants.php?ok=1');
exit;
