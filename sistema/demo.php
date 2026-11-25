<?php
// ============================================================
// ENTRADA NA DEMONSTRACAO DO PAINEL
// ------------------------------------------------------------
// O visitante entra na conta marcada com usuarios.demo = 1 sem
// digitar senha. Só contas com a marca "demo" entram por aqui, e
// elas continuam somente leitura (bb_demo_proteger_requisicao).
// Nao existe senha publica: nada para vazar ou ser trocado.
// ============================================================
@session_start();
require_once(__DIR__ . '/conexao.php');

// Mesmo limite de tentativas do login normal, por IP.
$espera = bb_rate_limit_login();
if ($espera > 0) {
    echo "<script>window.alert('Muitas tentativas. Tente novamente em " . (int) ceil($espera / 60) . " minuto(s).')</script>";
    echo "<script>window.location='index.php'</script>";
    exit;
}

$demo = false;
try {
    $st = $pdo->query("SELECT id, nome, nivel FROM usuarios WHERE demo = 1 AND ativo = 'Sim' ORDER BY id ASC LIMIT 1");
    $demo = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;
} catch (Exception $e) {
    $demo = false; // coluna ainda nao migrada
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
// Amarra a sessao ao banco do estabelecimento resolvido pelo dominio
$_SESSION['bb_tenant_db'] = bb_banco_atual();
// Marca a sessao como demonstracao: escrita bloqueada em todo o painel
$_SESSION['bb_demo'] = 1;

echo "<script>window.location='painel'</script>";
