<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'receber';
@session_start();
$id_usuario = $_SESSION['id'];


$id = $_POST['id'];

$pdo->prepare("UPDATE $tabela SET pago = 'Sim', usuario_baixa = :v1, data_pgto = curDate() where id = :v2")->execute(array(':v1' => $id_usuario, ':v2' => $id));

echo 'Baixado com Sucesso';
 ?>
