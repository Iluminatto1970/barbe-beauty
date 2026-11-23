<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);

$id = (int) @$_GET['id'];
if ($id > 0) {
    // Estabelecimentos ficam sem plano em vez de perder a referencia
    $pdo->prepare("UPDATE tenants SET plano_id = NULL WHERE plano_id = :id")->execute(array(':id' => $id));
    $pdo->prepare("DELETE FROM saas_planos WHERE id = :id")->execute(array(':id' => $id));
}

header('Location: planos.php');
exit;
