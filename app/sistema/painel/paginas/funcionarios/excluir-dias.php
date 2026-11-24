<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'dias';

$id = $_POST['id'];


$pdo->query("DELETE from $tabela where id = '$id'");
echo 'Excluído com Sucesso';
 ?>