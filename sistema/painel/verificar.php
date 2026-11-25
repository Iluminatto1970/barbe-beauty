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

// Sunset MD5: forca troca de senha para usuarios migrados de MD5 legado
$st_flag = $pdo->prepare("SELECT precisa_trocar_senha, demo FROM usuarios WHERE id = :id LIMIT 1");
$st_flag->execute(array(':id' => $_SESSION['id']));
$row_flag = $st_flag->fetch(PDO::FETCH_ASSOC);
if ($row_flag) {
	$_SESSION['bb_demo'] = ((int) $row_flag['demo'] === 1) ? 1 : 0;
}
// Conta demo usa senha padrao e nao escreve: nao faz sentido exigir troca
if ($row_flag && (int)$row_flag['precisa_trocar_senha'] === 1 && !bb_usuario_demo()) {
	echo "<script>window.location='../trocar-senha.php'</script>";
	exit();
}
?>
