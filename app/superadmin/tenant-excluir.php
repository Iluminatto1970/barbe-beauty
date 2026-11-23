<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);

// Exclusao so por POST com token da sessao (evita remocao por link externo)
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_POST['csrf'])
    || empty($_SESSION['sa_csrf'])
    || !hash_equals((string) $_SESSION['sa_csrf'], (string) $_POST['csrf'])) {
    header('Location: tenants.php?erro=' . urlencode('confirmação inválida, tente novamente'));
    exit;
}

$id = (int) @$_POST['id'];
if ($id > 0) {
    $q = $pdo->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
    $q->bindValue(':id', $id, PDO::PARAM_INT);
    $q->execute();
    $tenant = $q->fetch(PDO::FETCH_ASSOC);

    if ($tenant) {
        // Remove o banco do cliente, nunca o banco de controle
        $db = trim((string) @$tenant['db_name']);
        $prefixo = bb_tenant_prefixo();
        if ($db !== ''
            && $db !== bb_banco_controle()
            && strpos($db, $prefixo) === 0
            && preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
            $pdo->exec("DROP DATABASE IF EXISTS `$db`");
        }

        $pdo->prepare("DELETE FROM saas_pagamentos WHERE tenant_id = :id")->execute(array(':id' => $id));
        $pdo->prepare("DELETE FROM tenant_dominios WHERE tenant_id = :id")->execute(array(':id' => $id));
        $pdo->prepare("DELETE FROM tenants WHERE id = :id")->execute(array(':id' => $id));
    }
}

header('Location: tenants.php?ok=1');
exit;
