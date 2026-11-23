<?php
// Cria (ou recria o que falta) o banco de dados do estabelecimento
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: tenants.php');
    exit;
}

$resultado = bb_tenant_provisionar($pdo, $id);
if ($resultado['ok']) {
    header('Location: tenants.php?ok=1');
} else {
    header('Location: tenants.php?erro=' . urlencode('não foi possível preparar o banco: ' . $resultado['erro']));
}
exit;
