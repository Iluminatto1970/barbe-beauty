<?php
require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/../sistema/email.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
bb_tenant_garantir_colunas($pdo);

$id = (int) @$_GET['id'];
if ($id <= 0) {
    header('Location: tenants.php');
    exit;
}

$q = $pdo->prepare("SELECT status FROM tenants WHERE id = :id");
$q->bindValue(':id', $id);
$q->execute();
$status = $q->fetchColumn();

if ($status !== false) {
    $novo = ($status == 'ativo') ? 'suspenso' : 'ativo';
    $up = $pdo->prepare("UPDATE tenants SET status = :status WHERE id = :id");
    $up->bindValue(':status', $novo);
    $up->bindValue(':id', $id);
    $up->execute();

    // Ao liberar manualmente, envia as instrucoes de acesso (uma vez)
    if ($novo === 'ativo') {
        try {
            bb_boas_vindas_enviar($pdo, $id);
        } catch (Exception $e) {
            // O acesso permanece valido mesmo sem o e-mail
        }
    }
}

header('Location: tenants.php');
exit;
