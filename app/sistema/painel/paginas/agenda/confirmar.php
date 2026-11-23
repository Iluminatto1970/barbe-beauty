<?php
require_once("../../../conexao.php");
@session_start();

$id = @$_POST['id'];

if ($id == "") {
	echo 'Agendamento inválido!';
	exit();
}

$query = $pdo->query("SELECT * FROM agendamentos where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($res) == 0) {
	echo 'Agendamento não encontrado!';
	exit();
}

$pdo->query("UPDATE agendamentos SET status = 'Agendado' WHERE id = '$id'");

echo 'Agendamento confirmado!';
?>
