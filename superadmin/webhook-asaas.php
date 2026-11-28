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
// A liberacao do acesso (status, vencimento, plano, banco e
// e-mail) fica em asaas_liberar_tenant(), a mesma rotina usada
// pela tela de pagamento quando o webhook atrasa.
// ============================================================

require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/_asaas.php');

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

    if ($tenant) {
        $liberacao = asaas_liberar_tenant($pdo, $tenant, $pagamento, $nome_evento);

        // Falha ao gravar: responde erro para o Asaas reenviar o evento
        if (empty($liberacao['registro']['ok'])) {
            error_log('asaas-webhook: pagamento ' . (string) $pagamento['id']
                . ' do estabelecimento ' . (int) $tenant['id'] . ' nao registrado: '
                . (string) $liberacao['registro']['erro']);
            wh_responder(500, 'Falha ao registrar o pagamento.');
        }

        if (!empty($liberacao['ativacao']) && empty($liberacao['ativacao']['ok'])) {
            error_log('asaas-webhook: pagamento ' . (string) $pagamento['id']
                . ' registrado, mas o acesso do estabelecimento ' . (int) $tenant['id']
                . ' nao foi liberado: ' . (string) $liberacao['ativacao']['erro']);
            wh_responder(500, 'Falha ao liberar o acesso.');
        }
    }
}

if ($nome_evento === 'SUBSCRIPTION_DELETED' && isset($evento['subscription']['id'])) {
    $q = $pdo->prepare("UPDATE tenants SET asaas_assinatura = 'cancelada' WHERE asaas_subscription_id = :sid");
    $q->bindValue(':sid', (string) $evento['subscription']['id']);
    $q->execute();
}

wh_responder(200, 'Evento recebido.');
