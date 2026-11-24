<?php
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'clientes';

$id = $_POST['id'];

$query = $pdo->prepare("UPDATE $tabela SET alertado = 'Sim' WHERE id = :id");
$query->execute(array(':id' => $id));
echo 'Salvo com Sucesso';
?>
