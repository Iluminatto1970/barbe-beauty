<?php
// ============================================================
// WEBHOOK DO ASAAS
// ------------------------------------------------------------
// Configure no painel do Asaas a URL:
//   https://SEU-DOMINIO/superadmin/webhook-asaas.php
// e o mesmo token salvo em ASAAS_WEBHOOK_TOKEN (header
// asaas-access-token). Eventos: PAYMENT_RECEIVED,
// PAYMENT_CONFIRMED, PAYMENT_OVERDUE, PAYMENT_REFUNDED e
// SUBSCRIPTION_DELETED.
// ============================================================

require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/_asaas.php');
require_once(__DIR__ . '/../sistema/email.php');

header('Content-Type: application/json; charset=utf-8');

function wh_responder($codigo, $mensagem)
{
    http_response_code($codigo);
    echo json_encode(array('mensagem' => $mensagem));
    exit;
}

$cfg = asaas_config();
if ($cfg['webhook'] === '') {
    wh_responder(503, 'Webhook sem token configurado.');
}

$token_recebido = isset($_SERVER['HTTP_ASAAS_ACCESS_TOKEN']) ? (string) $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] : '';
if ($token_recebido === '' || !hash_equals($cfg['webhook'], $token_recebido)) {
    wh_responder(401, 'Token inválido.');
}

$corpo = file_get_contents('php://input');
$evento = json_decode($corpo, true);
if (!is_array($evento) || empty($evento['event'])) {
    wh_responder(400, 'Evento inválido.');
}

$nome_evento = (string) $evento['event'];

try {
    sa_garantir_tabelas($pdo);
    asaas_garantir_colunas($pdo);
} catch (Exception $e) {
    wh_responder(500, 'Falha ao preparar o banco.');
}

$pagamento = isset($evento['payment']) && is_array($evento['payment']) ? $evento['payment'] : null;

if ($pagamento && !empty($pagamento['id'])) {
    $tenant = asaas_tenant_por_pagamento($pdo, $pagamento);
    $payment_id = (string) $pagamento['id'];
    $valor      = isset($pagamento['value']) ? (float) $pagamento['value'] : 0;
    $venc       = isset($pagamento['dueDate']) ? substr((string) $pagamento['dueDate'], 0, 10) : date('Y-m-d');
    $link       = isset($pagamento['invoiceUrl']) ? (string) $pagamento['invoiceUrl'] : '';
    $referencia = substr($venc, 0, 7);

    $pago = in_array($nome_evento, array('PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'), true);
    $estornado = in_array($nome_evento, array('PAYMENT_REFUNDED', 'PAYMENT_DELETED', 'PAYMENT_CHARGEBACK_REQUESTED'), true);

    $status_local = $pago ? 'pago' : ($estornado ? 'estornado' : 'pendente');
    $pago_em = $pago ? date('Y-m-d H:i:s') : null;

    if ($tenant) {
        $existe = $pdo->prepare("SELECT id FROM saas_pagamentos WHERE asaas_payment_id = :pid LIMIT 1");
        $existe->execute(array(':pid' => $payment_id));
        $id_existente = $existe->fetchColumn();

        try {
            if ($id_existente) {
                $q = $pdo->prepare("UPDATE saas_pagamentos
                    SET status = :status, valor = :valor, pago_em = :pago, asaas_status = :astatus, asaas_link = :link
                    WHERE id = :id");
                $q->bindValue(':id', $id_existente, PDO::PARAM_INT);
            } else {
                $q = $pdo->prepare("INSERT INTO saas_pagamentos
                    (tenant_id, valor, referencia, status, pago_em, asaas_payment_id, asaas_status, asaas_link, asaas_venc, criado_em)
                    VALUES (:tenant, :valor, :ref, :status, :pago, :pid, :astatus, :link, :venc, NOW())");
                $q->bindValue(':tenant', (int) $tenant['id'], PDO::PARAM_INT);
                $q->bindValue(':ref', $referencia);
                $q->bindValue(':pid', $payment_id);
                $q->bindValue(':venc', $venc);
            }
            $q->bindValue(':valor', $valor);
            $q->bindValue(':status', $status_local);
            $q->bindValue(':astatus', $nome_evento);
            $q->bindValue(':link', $link);
            if ($pago_em === null) {
                $q->bindValue(':pago', null, PDO::PARAM_NULL);
            } else {
                $q->bindValue(':pago', $pago_em);
            }
            $q->execute();
        } catch (Exception $e) {
            // indice unico pode já ter o registro: segue para atualizar o tenant
        }

        if ($pago) {
            // Vencimento = dueDate + 1 mes, nunca retrocede
            $q = $pdo->prepare("UPDATE tenants
                SET status = 'ativo',
                    asaas_assinatura = 'ativa',
                    vencimento = GREATEST(COALESCE(vencimento, CURDATE()), DATE_ADD(:venc, INTERVAL 1 MONTH))
                WHERE id = :id");
            $q->bindValue(':venc', $venc);
            $q->bindValue(':id', (int) $tenant['id'], PDO::PARAM_INT);
            $q->execute();

            // Cria o banco de dados do cliente (idempotente)
            try {
                bb_tenant_provisionar($pdo, (int) $tenant['id']);
            } catch (Exception $e) {
                // O pagamento ja foi registrado; o super admin pode provisionar depois
            }

            // Avisa o cliente que o acesso esta liberado (uma vez por estabelecimento)
            try {
                bb_boas_vindas_enviar($pdo, (int) $tenant['id']);
            } catch (Exception $e) {
                // O acesso permanece valido mesmo sem o e-mail
            }
        }
    }
}

if ($nome_evento === 'SUBSCRIPTION_DELETED' && isset($evento['subscription']['id'])) {
    $q = $pdo->prepare("UPDATE tenants SET asaas_assinatura = 'cancelada' WHERE asaas_subscription_id = :sid");
    $q->bindValue(':sid', (string) $evento['subscription']['id']);
    $q->execute();
}

wh_responder(200, 'Evento recebido.');
