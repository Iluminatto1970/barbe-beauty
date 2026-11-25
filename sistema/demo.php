<?php
// ============================================================
// ENTRADA NA DEMONSTRACAO DO PAINEL
// ------------------------------------------------------------
// O visitante entra na conta marcada com usuarios.demo = 1 sem
// digitar senha. A partir daqui a sessao passa a usar o banco
// ficticio da demonstracao (bb_demo), nunca o banco real.
// So contas com a marca "demo" entram, e elas continuam somente
// leitura (bb_demo_proteger_requisicao). Nao existe senha
// publica: nada para vazar ou ser trocado.
// ============================================================
@session_start();
require_once(__DIR__ . '/conexao.php');

if (!bb_demo_disponivel()) {
    echo "<script>window.alert('A demonstração não está disponível neste estabelecimento.')</script>";
    echo "<script>window.location='index.php'</script>";
    exit;
}

// Mesmo limite de tentativas do login normal, por IP.
$espera = bb_rate_limit_login();
if ($espera > 0) {
    echo "<script>window.alert('Muitas tentativas. Tente novamente em " . (int) ceil($espera / 60) . " minuto(s).')</script>";
    echo "<script>window.location='index.php'</script>";
    exit;
}

// O banco ficticio precisa estar pronto antes de montar a sessao.
// A conexao de controle continua separada da conexao do tenant.
try {
    $pdo_demo = bb_demo_garantir_banco(($pdo_controle instanceof PDO) ? $pdo_controle : $pdo);
} catch (Exception $e) {
    echo "<script>window.alert('A demonstração está temporariamente indisponível.')</script>";
    echo "<script>window.location='index.php'</script>";
    exit;
}

// Conta demo do banco ficticio (nunca um usuario real).
$demo = false;
try {
    $st = $pdo_demo->query("SELECT id, nome, nivel FROM usuarios WHERE demo = 1 AND ativo = 'Sim' ORDER BY id ASC LIMIT 1");
    $demo = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;
} catch (Exception $e) {
    $demo = false;
}

if (!$demo) {
    echo "<script>window.alert('A demonstração não está disponível neste estabelecimento.')</script>";
    echo "<script>window.location='index.php'</script>";
    exit;
}

bb_rate_limit_login_ok();
session_regenerate_id(true);
$_SESSION['id'] = (int) $demo['id'];
$_SESSION['nivel'] = (string) $demo['nivel'];
$_SESSION['nome'] = (string) $demo['nome'];
$_SESSION['bb_demo'] = 1;
// Amarra a sessao ao banco FICTICIO da demonstracao
$_SESSION['bb_tenant_db'] = (string) $pdo_demo->query('SELECT DATABASE()')->fetchColumn();

echo "<script>window.location='painel'</script>";
