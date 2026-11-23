<?php
require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/_asaas.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);

$id = (int) @$_GET['id'];
if ($id <= 0) {
    header('Location: asaas.php');
    exit;
}

$q = $pdo->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
$q->bindValue(':id', $id, PDO::PARAM_INT);
$q->execute();
$tenant = $q->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    header('Location: asaas.php?erro=' . urlencode('Estabelecimento não encontrado.'));
    exit;
}

$r = asaas_cancelar_assinatura($pdo, $tenant);

if (!$r['ok']) {
    header('Location: asaas.php?erro=' . urlencode($r['erro']));
    exit;
}

header('Location: asaas.php?ok=' . urlencode('Assinatura cancelada no Asaas.'));
exit;
