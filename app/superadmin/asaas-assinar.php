<?php
require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/_asaas.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);

$tenant_id  = (int) @$_POST['tenant_id'];
$valor      = str_replace(',', '.', (string) @$_POST['valor']);
$forma      = (string) @$_POST['forma'];
$vencimento = (string) @$_POST['vencimento'];

if ($tenant_id <= 0) {
    header('Location: asaas.php?erro=' . urlencode('Estabelecimento inválido.'));
    exit;
}

$q = $pdo->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
$q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
$q->execute();
$tenant = $q->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    header('Location: asaas.php?erro=' . urlencode('Estabelecimento não encontrado.'));
    exit;
}

if (empty($tenant['email'])) {
    header('Location: asaas.php?erro=' . urlencode('Cadastre um e-mail no estabelecimento antes de assinar.'));
    exit;
}

$r = asaas_criar_assinatura($pdo, $tenant, $valor, $forma, $vencimento);

if (!$r['ok']) {
    header('Location: asaas.php?erro=' . urlencode($r['erro']));
    exit;
}

header('Location: asaas.php?ok=' . urlencode('Assinatura criada no Asaas: ' . $r['id']));
exit;
