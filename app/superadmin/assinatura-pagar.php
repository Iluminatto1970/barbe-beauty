<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);

$tenant_id  = (int) @$_POST['tenant_id'];
$valor      = (float) str_replace(',', '.', (string) @$_POST['valor']);
$referencia = trim((string) @$_POST['referencia']);
$status     = (@$_POST['status'] == 'pendente') ? 'pendente' : 'pago';

if ($referencia === '') {
    $referencia = date('Y-m');
}

if ($tenant_id > 0) {
    $q = $pdo->prepare("SELECT id FROM tenants WHERE id = :id");
    $q->bindValue(':id', $tenant_id);
    $q->execute();

    if ($q->fetchColumn()) {
        $ins = $pdo->prepare("INSERT INTO saas_pagamentos (tenant_id, valor, referencia, status, pago_em, criado_em)
                              VALUES (:tenant, :valor, :ref, :status, :pago, NOW())");
        $ins->bindValue(':tenant', $tenant_id);
        $ins->bindValue(':valor', $valor);
        $ins->bindValue(':ref', $referencia);
        $ins->bindValue(':status', $status);
        if ($status == 'pago') {
            $ins->bindValue(':pago', date('Y-m-d H:i:s'));
        } else {
            $ins->bindValue(':pago', null, PDO::PARAM_NULL);
        }
        $ins->execute();

        if ($status == 'pago') {
            $up = $pdo->prepare("UPDATE tenants
                SET status = 'ativo',
                    vencimento = DATE_ADD(GREATEST(COALESCE(vencimento, CURDATE()), CURDATE()), INTERVAL 1 MONTH)
                WHERE id = :id");
            $up->bindValue(':id', $tenant_id);
            $up->execute();
        }
    }
}

header('Location: assinaturas.php?ok=1');
exit;
