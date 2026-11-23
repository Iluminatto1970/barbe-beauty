<?php
@session_start();

// Carrega a conexao (e a resolucao multi-tenant) antes de validar a sessao
require_once(__DIR__ . '/../conexao.php');

if (@$_SESSION['id'] == "") {
	echo "<script>window.location='../index.php'</script>";
	exit();
}

// A sessao so vale no banco de dados do estabelecimento em que foi criada
$sessao_banco = isset($_SESSION['bb_tenant_db']) ? (string) $_SESSION['bb_tenant_db'] : '';
if ($sessao_banco === '' || $sessao_banco !== bb_banco_atual()) {
	@session_destroy();
	echo "<script>window.location='../index.php'</script>";
	exit();
}

// Assinatura pendente, suspensa ou vencida bloqueia o painel
$bloqueio = bb_tenant_bloqueio();
if ($bloqueio !== '') {
	@session_destroy();
	echo "<script>window.alert(" . json_encode($bloqueio) . ")</script>";
	echo "<script>window.location='../index.php'</script>";
	exit();
}
?>
