<?php
require_once("../../../conexao.php");
bb_csrf_check_request();
@session_start();

$id = @$_POST['id'];

if ($id == "") {
	echo 'Agendamento inválido!';
	exit();
}

$query = $pdo->prepare("SELECT * FROM agendamentos where id = :id");
$query->execute(array(':id' => $id));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($res) == 0) {
	echo 'Agendamento não encontrado!';
	exit();
}

$up = $pdo->prepare("UPDATE agendamentos SET status = 'Agendado' WHERE id = :id");
$up->execute(array(':id' => $id));

echo 'Agendamento confirmado!';
?>
