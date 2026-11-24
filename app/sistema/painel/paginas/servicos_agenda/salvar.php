<?php 
require_once("../../../conexao.php");
$tabela = 'receber';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }
bb_csrf_check_request();
$data_atual = date('Y-m-d');

@session_start();
$usuario_logado = @$_SESSION['id'];

$cliente = $_POST['cliente'];
$data_pgto = $_POST['data_pgto'];
$id = @$_POST['id'];
$valor_serv = $_POST['valor_serv'];
$funcionario = $_POST['funcionario'];
$servico = $_POST['servico'];




$query = $pdo->prepare("SELECT * FROM servicos where id = :servico");
$query->bindValue(':servico', $servico);
$query->execute();
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
	$stmt = $pdo->prepare("INSERT INTO pagar SET descricao = :descricao2, tipo = 'Comissão', valor = :valor_comissao, data_lanc = :data_pgto, data_venc = :data_pgto, usuario_lanc = :usuario_logado, foto = 'sem-foto.jpg', pago = 'Não', funcionario = :funcionario, servico = :servico, cliente = :cliente");
$stmt->bindValue(':cliente', $cliente);
$stmt->bindValue(':data_pgto', $data_pgto);
$stmt->bindValue(':descricao2', $descricao2);
$stmt->bindValue(':funcionario', $funcionario);
$stmt->bindValue(':servico', $servico);
$stmt->bindValue(':usuario_logado', $usuario_logado);
$stmt->bindValue(':valor_comissao', $valor_comissao);
$stmt->execute();

}else{
	$pago = 'Não';
	$data_pgto2 = '';
	$usuario_baixa = 0;
}

$query2 = $pdo->prepare("SELECT * FROM servicos where id = :servico");
$query2->bindValue(':servico', $servico);
$query2->execute();
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$dias_retorno = $res2[0]['dias_retorno'];
$nome_servico = $res2[0]['nome'];

//dados do cliente
$query2 = $pdo->prepare("SELECT * FROM clientes where id = :cliente");
$query2->bindValue(':cliente', $cliente);
$query2->execute();
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$total_cartoes = $res2[0]['cartoes'];
$cartoes = $total_cartoes + 1;
$data_retorno = date('Y-m-d', strtotime("+$dias_retorno days",strtotime($data_atual)));

$stmt = $pdo->prepare("INSERT INTO $tabela SET descricao = :nome_servico, tipo = 'Serviço', valor = :valor_serv, data_lanc = curDate(), data_venc = :data_pgto, data_pgto = :data_pgto2, usuario_lanc = :usuario_logado, usuario_baixa = :usuario_baixa, foto = 'sem-foto.jpg', pessoa = :cliente, pago = :pago, servico = :servico, funcionario = :funcionario");
$stmt->bindValue(':cliente', $cliente);
$stmt->bindValue(':data_pgto', $data_pgto);
$stmt->bindValue(':data_pgto2', $data_pgto2);
$stmt->bindValue(':funcionario', $funcionario);
$stmt->bindValue(':nome_servico', $nome_servico);
$stmt->bindValue(':pago', $pago);
$stmt->bindValue(':servico', $servico);
$stmt->bindValue(':usuario_baixa', $usuario_baixa);
$stmt->bindValue(':usuario_logado', $usuario_logado);
$stmt->bindValue(':valor_serv', $valor_serv);
$stmt->execute();


$stmt = $pdo->prepare("UPDATE clientes SET cartoes = :cartoes, data_retorno = :data_retorno, ultimo_servico = :servico, alertado = 'Não' where id = :cliente");
$stmt->bindValue(':cartoes', $cartoes);
$stmt->bindValue(':cliente', $cliente);
$stmt->bindValue(':data_retorno', $data_retorno);
$stmt->bindValue(':servico', $servico);
$stmt->execute();

echo 'Salvo com Sucesso'; 

?>