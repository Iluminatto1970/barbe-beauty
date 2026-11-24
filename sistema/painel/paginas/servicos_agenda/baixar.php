<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'receber';
@session_start();
$id_usuario = $_SESSION['id'];


$id = $_POST['id'];

$query = $pdo->prepare("SELECT * FROM $tabela where id = :v1");
$query->execute(array(':v1' => $id));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$funcionario = $res[0]['funcionario'];
$servico = $res[0]['servico'];
$cliente = $res[0]['pessoa'];
$descricao = 'Comissão - '.$res[0]['descricao'];

$query = $pdo->prepare("SELECT * FROM servicos where id = :v1");
$query->execute(array(':v1' => $servico));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$valor = $res[0]['valor'];
$comissao = $res[0]['comissao'];

if($tipo_comissao == 'Porcentagem'){
	$valor_comissao = ($comissao * $valor) / 100;
}else{
	$valor_comissao = $comissao;
}


$pdo->prepare("UPDATE $tabela SET pago = 'Sim', usuario_baixa = :v1, data_pgto = curDate() where id = :v2")->execute(array(':v1' => $id_usuario, ':v2' => $id));


//lançar a conta a pagar para a comissão do funcionário
$pdo->prepare("INSERT INTO pagar SET descricao = :v1, tipo = 'Comissão', valor = :v2, data_lanc = curDate(), data_venc = curDate(), usuario_lanc = :v3, foto = 'sem-foto.jpg', pago = 'Não', funcionario = :v4, servico = :v5, cliente = :v6")->execute(array(':v1' => $descricao, ':v2' => $valor_comissao, ':v3' => $id_usuario, ':v4' => $funcionario, ':v5' => $servico, ':v6' => $cliente));

echo 'Baixado com Sucesso';
 ?>
