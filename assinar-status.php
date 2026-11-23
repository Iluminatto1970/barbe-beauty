<?php
// Consulta simples do status da assinatura (usada pela pagina de pagamento)
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

$q = $pdo->prepare("SELECT status, vencimento FROM tenants WHERE token = :token LIMIT 1");
$q->bindValue(':token', $token);
$q->execute();
$tenant = $q->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    http_response_code(404);
    echo json_encode(array('erro' => 'cadastro não encontrado'));
    exit;
}

echo json_encode(array(
    'pago'       => ($tenant['status'] === 'ativo'),
    'status'     => $tenant['status'],
    'vencimento' => $tenant['vencimento'],
));
