<?php
$tabela = 'receber';
require_once("../../../conexao.php");
bb_csrf_check_request();
$data_atual = date('Y-m-d');

@session_start();
$usuario_logado = @$_SESSION['id'];

$cliente = $_POST['cliente_agd'];
$data_pgto = $_POST['data_pgto'];
$id_agd = @$_POST['id_agd'];
$valor_serv = $_POST['valor_serv_agd'];
$descricao = $_POST['descricao_serv_agd'];
$funcionario = $_POST['funcionario_agd'];
$servico = $_POST['servico_agd'];

$query = $pdo->prepare("SELECT * FROM servicos where id = :servico");
$query->execute(array(':servico' => $servico));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$valor = $res[0]['valor'];
$comissao = $res[0]['comissao'];
$descricao = $res[0]['nome'];
$descricao2 = 'Comissão - '.$res[0]['nome'];

if($tipo_comissao == 'Porcentagem'){
	$valor_comissao = ($comissao * $valor) / 100;
}else{
	$valor_comissao = $comissao;
}

if(strtotime($data_pgto) <=  strtotime($data_atual)){
	$pago = 'Sim';
	$data_pgto2 = $data_pgto;
	$usuario_baixa = $usuario_logado;

	//lançar a conta a pagar para a comissão do funcionário
	$ins = $pdo->prepare("INSERT INTO pagar SET descricao = :descricao, tipo = 'Comissão', valor = :valor, data_lanc = :data_lanc, data_venc = :data_venc, usuario_lanc = :usuario_lanc, foto = 'sem-foto.jpg', pago = 'Não', funcionario = :funcionario, servico = :servico, cliente = :cliente");
	$ins->execute(array(':descricao' => $descricao2, ':valor' => $valor_comissao, ':data_lanc' => $data_pgto, ':data_venc' => $data_pgto, ':usuario_lanc' => $usuario_logado, ':funcionario' => $funcionario, ':servico' => $servico, ':cliente' => $cliente));
}else{
	$pago = 'Não';
	$data_pgto2 = '';
	$usuario_baixa = 0;
}

$query2 = $pdo->prepare("SELECT * FROM servicos where id = :servico");
$query2->execute(array(':servico' => $servico));
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$dias_retorno = $res2[0]['dias_retorno'];

//dados do cliente
$query2 = $pdo->prepare("SELECT * FROM clientes where id = :cliente");
$query2->execute(array(':cliente' => $cliente));
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$total_cartoes = $res2[0]['cartoes'];

if($total_cartoes >= $quantidade_cartoes){
	$cartoes = 0;
}else{
	$cartoes = $total_cartoes + 1;
}

$data_retorno = date('Y-m-d', strtotime("+$dias_retorno days",strtotime($data_atual)));

$ins2 = $pdo->prepare("INSERT INTO $tabela SET descricao = :descricao, tipo = 'Serviço', valor = :valor, data_lanc = curDate(), data_venc = :data_venc, data_pgto = :data_pgto, usuario_lanc = :usuario_lanc, usuario_baixa = :usuario_baixa, foto = 'sem-foto.jpg', pessoa = :pessoa, pago = :pago, servico = :servico, funcionario = :funcionario");
$ins2->execute(array(':descricao' => $descricao, ':valor' => $valor_serv, ':data_venc' => $data_pgto, ':data_pgto' => $data_pgto2, ':usuario_lanc' => $usuario_logado, ':usuario_baixa' => $usuario_baixa, ':pessoa' => $cliente, ':pago' => $pago, ':servico' => $servico, ':funcionario' => $funcionario));

$up = $pdo->prepare("UPDATE agendamentos SET status = 'Concluído' where id = :id");
$up->execute(array(':id' => $id_agd));

$up2 = $pdo->prepare("UPDATE clientes SET cartoes = :cartoes, data_retorno = :data_retorno, ultimo_servico = :servico, alertado = 'Não' where id = :cliente");
$up2->execute(array(':cartoes' => $cartoes, ':data_retorno' => $data_retorno, ':servico' => $servico, ':cliente' => $cliente));

echo 'Salvo com Sucesso';
?>
