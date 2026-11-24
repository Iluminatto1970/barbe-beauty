<?php
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'horarios';

$id = $_POST['id'];

$query = $pdo->prepare("DELETE from $tabela where id = :id");
$query->execute(array(':id' => $id));
echo 'Excluído com Sucesso';
?>
