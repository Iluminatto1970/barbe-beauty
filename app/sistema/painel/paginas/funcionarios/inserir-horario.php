<?php
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'horarios';

$id = $_POST['id'];
$horario = $_POST['horario'];

$query = $pdo->prepare("INSERT INTO $tabela SET horario = :horario, funcionario = :id");
$query->execute(array(':horario' => $horario, ':id' => $id));

echo 'Salvo com Sucesso';
?>
