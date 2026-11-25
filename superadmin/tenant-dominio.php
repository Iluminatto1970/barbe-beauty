<?php
// ============================================================
// DOMINIO PROPRIO DO ESTABELECIMENTO (super admin)
// ------------------------------------------------------------
// Registrar, verificar e remover. O host so passa a servir
// depois que a verificacao grava verificado_em.
// ============================================================
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tenants.php');
    exit;
}

sa_garantir_tabelas($pdo);
bb_tenant_garantir_colunas($pdo);
bb_dominio_garantir_colunas($pdo);

$id = (int) @$_POST['id'];
$acao = isset($_POST['acao']) ? (string) $_POST['acao'] : '';
$host = bb_dominio_normalizar(isset($_POST['host']) ? $_POST['host'] : '');
$csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';

$voltar = function ($tipo, $mensagem) use ($id) {
    $destino = 'tenants.php';
    if ($id > 0) {
        $destino .= '?id=' . $id . '&' . $tipo . '=' . urlencode($mensagem);
    } else {
        $destino .= '?' . $tipo . '=' . urlencode($mensagem);
    }
    header('Location: ' . $destino);
    exit;
};

if (empty($_SESSION['sa_csrf']) || !hash_equals((string) $_SESSION['sa_csrf'], $csrf)) {
    $voltar('erro', 'Sessão expirada. Tente novamente.');
}

if ($id <= 0) {
    $id = 0;
    $voltar('erro', 'Estabelecimento inválido.');
}

if ($acao === 'registrar') {
    $resultado = bb_dominio_registrar($pdo, $id, $host);
    if (!$resultado['ok']) {
        $voltar('erro', $resultado['motivo']);
    }
    $voltar('ok', 'Domínio ' . $resultado['host'] . ' registrado. Crie o registro TXT (ou CNAME) exibido abaixo e clique em Verificar. O site só passa a responder depois da verificação.');
}

if ($acao === 'verificar') {
    $resultado = bb_dominio_verificar($pdo, $id, $host);
    if ($resultado['ok']) {
        $voltar('ok', $resultado['motivo']);
    }
    $voltar('erro', $resultado['motivo']);
}

if ($acao === 'remover') {
    if (bb_dominio_remover($pdo, $id, $host)) {
        $voltar('ok', 'Domínio próprio removido. O subdomínio do estabelecimento continua funcionando.');
    }
    $voltar('erro', 'Não foi possível remover este domínio.');
}

$voltar('erro', 'Ação desconhecida.');
