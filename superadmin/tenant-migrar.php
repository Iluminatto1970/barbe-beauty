<?php
// Aplica as migracoes pendentes em todos os bancos (controle + clientes)
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
bb_tenant_garantir_colunas($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tenants.php');
    exit;
}

$csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
if (empty($_SESSION['sa_csrf']) || !hash_equals((string) $_SESSION['sa_csrf'], $csrf)) {
    header('Location: tenants.php?erro=' . urlencode('sessão expirada, tente novamente'));
    exit;
}

$lista = $pdo->query("SELECT id, nome, slug, db_name FROM tenants ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$aplicadas = 0;
$bancos = array();
$falhas = array();

foreach ($lista as $tenant) {
    $db = bb_tenant_banco($tenant);
    if (isset($bancos[$db])) {
        continue;
    }
    $bancos[$db] = true;

    try {
        $pdo_db = ($db === bb_banco_controle()) ? $pdo : bb_pdo_banco($db);
        $resultado = bb_migracoes_sincronizar($pdo_db);
    } catch (Exception $e) {
        $falhas[] = $tenant['nome'] . ': ' . $e->getMessage();
        continue;
    }

    if (!$resultado['ok']) {
        $falhas[] = $tenant['nome'] . ': ' . $resultado['erro'];
        continue;
    }

    $pdo->prepare("UPDATE tenants SET schema_version = :versao WHERE id = :id")
        ->execute(array(':versao' => (int) $resultado['versao'], ':id' => (int) $tenant['id']));

    $aplicadas += count($resultado['aplicadas']);
}

if (count($falhas) > 0) {
    header('Location: tenants.php?erro=' . urlencode('bancos com falha: ' . implode(' | ', $falhas)));
    exit;
}

header('Location: tenants.php?ok=1&migracoes=' . (int) $aplicadas . '&bancos=' . count($bancos));
exit;
