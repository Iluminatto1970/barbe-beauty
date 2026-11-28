<?php
require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/_asaas.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);

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
        // Dois cliques nao podem prorrogar o vencimento duas vezes
        $dup = $pdo->prepare("SELECT COUNT(*) FROM saas_pagamentos
            WHERE tenant_id = :tenant AND referencia = :ref AND status = 'pago' AND asaas_payment_id IS NULL");
        $dup->execute(array(':tenant' => $tenant_id, ':ref' => $referencia));
        $ja_pago = ((int) $dup->fetchColumn()) > 0;

        if (!$ja_pago) {
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
        }

        // Mesma liberacao do webhook: ativo, vencimento, plano pago, banco
        // do cliente e e-mail com as instrucoes de acesso.
        if ($status == 'pago') {
            try {
                asaas_ativar_acesso($pdo, $tenant_id, date('Y-m-d'));
            } catch (Exception $e) {
                error_log('assinatura-pagar: estabelecimento ' . $tenant_id . ' nao liberado: ' . $e->getMessage());
            }
        }
    }
}

header('Location: assinaturas.php?ok=1');
exit;
