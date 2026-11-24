<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'pagar';
@session_start();
$id_usuario = $_SESSION['id'];

$dataInicial = @$_POST['data_inicial'];
$dataFinal = @$_POST['data_final'];
$funcionario = @$_POST['id_funcionario'];

$pdo->prepare("UPDATE $tabela SET pago = 'Sim', usuario_baixa = :v1, data_pgto = curDate() where data_lanc >= :v2 and data_lanc <= :v3 and pago = 'Não' and funcionario LIKE :v4 and tipo = 'Comissão'")->execute(array(':v1' => $id_usuario, ':v2' => $dataInicial, ':v3' => $dataFinal, ':v4' => $funcionario));

echo 'Baixado com Sucesso';
 ?>
