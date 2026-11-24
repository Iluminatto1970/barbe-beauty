<?php
require_once("../sistema/conexao.php");

$id = @$_POST['id'];

$query = $pdo->prepare("DELETE FROM agendamentos where id = :id");
$query->execute(array(':id' => $id));

echo 'Cancelado com Sucesso';
?>
