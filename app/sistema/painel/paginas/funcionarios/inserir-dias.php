<?php
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'dias';

$id = $_POST['id'];
$dias = $_POST['dias'];

$query = $pdo->prepare("INSERT INTO $tabela SET dia = :dias, funcionario = :id");
$query->execute(array(':dias' => $dias, ':id' => $id));

echo 'Salvo com Sucesso';
?>
