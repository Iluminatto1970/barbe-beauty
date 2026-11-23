<?php
// Reenvia o e-mail de boas-vindas do estabelecimento
require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/../sistema/email.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
bb_tenant_garantir_colunas($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: tenants.php');
    exit;
}

$resultado = bb_boas_vindas_enviar($pdo, $id, true);
if ($resultado['ok'] && $resultado['enviado']) {
    header('Location: tenants.php?ok=1');
} else {
    $motivo = $resultado['erro'] !== '' ? $resultado['erro'] : 'e-mail não enviado';
    header('Location: tenants.php?erro=' . urlencode($motivo));
}
exit;
