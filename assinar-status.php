<?php
// Consulta simples do status da assinatura (usada pela pagina de pagamento).
// Se o webhook atrasar, esta chamada confere a cobranca na API e libera o
// acesso na hora (no maximo uma conferencia a cada 20 segundos por cliente).
require_once(__DIR__ . '/superadmin/_auth.php');
require_once(__DIR__ . '/superadmin/_asaas.php');
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$token = isset($_GET['t']) ? preg_replace('/[^a-f0-9]/i', '', (string) $_GET['t']) : '';
if ($token === '') {
    http_response_code(400);
    echo json_encode(array('erro' => 'token ausente'));
    exit;
}

$q = $pdo->prepare("SELECT * FROM tenants WHERE token = :token LIMIT 1");
$q->bindValue(':token', $token);
$q->execute();
$tenant = $q->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    http_response_code(404);
    echo json_encode(array('erro' => 'cadastro não encontrado'));
    exit;
}

$status = (string) $tenant['status'];

if ($status === 'pendente' && !empty($tenant['asaas_subscription_id']) && asaas_configurado()) {
    $checado = (string) (isset($tenant['asaas_checado_em']) ? $tenant['asaas_checado_em'] : '');
    if ($checado === '' || strtotime($checado) < time() - 20) {
        try {
            $pdo->prepare("UPDATE tenants SET asaas_checado_em = NOW() WHERE id = :id")
                ->execute(array(':id' => (int) $tenant['id']));
        } catch (Exception $e) {
            // coluna criada por asaas_garantir_colunas
        }

        $r = asaas_req('GET', '/subscriptions/' . $tenant['asaas_subscription_id'] . '/payments?limit=1');
        if ($r['ok'] && !empty($r['dados']['data'][0])) {
            $pagamento = $r['dados']['data'][0];
            if (asaas_status_pago(isset($pagamento['status']) ? $pagamento['status'] : '')) {
                $evento = (strtoupper((string) $pagamento['status']) === 'CONFIRMED')
                    ? 'PAYMENT_CONFIRMED' : 'PAYMENT_RECEIVED';
                $liberacao = asaas_liberar_tenant($pdo, $tenant, $pagamento, $evento);
                if (!empty($liberacao['pago'])) {
                    $status = 'ativo';
                    $tenant['vencimento'] = $liberacao['ativacao']['vencimento'];
                }
            }
        }
    }
}

echo json_encode(array(
    'pago'       => in_array($status, array('ativo', 'trial'), true),
    'status'     => $status,
    'vencimento' => $tenant['vencimento'],
));
