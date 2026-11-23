<?php 
require_once("../sistema/conexao.php");

$telefone = $_POST['telefone'];
$nome = $_POST['nome'];
$funcionario = $_POST['funcionario'];
$hora = @$_POST['hora'];
// O cliente pode escolher mais de um serviço (ids separados por virgula)
$ids_servicos = array();
foreach(explode(',', (string)@$_POST['servico']) as $cod_servico){
	$cod_servico = intval(trim($cod_servico));
	if($cod_servico > 0 and !in_array($cod_servico, $ids_servicos)){
		$ids_servicos[] = $cod_servico;
	}
}
if(count($ids_servicos) == 0){
	echo 'Escolha um Serviço para Agendar!';
	exit;
}
$servico = $ids_servicos[0];
$lista_servicos = implode(',', $ids_servicos);
$obs = $_POST['obs'];
$data = $_POST['data'];
$id = @$_POST['id'];

if($hora == ""){
	echo 'Escolha um Horário para Agendar!';
	exit();
}

// Não aceita agendamento em data passada
if($data < date('Y-m-d')){
	echo 'Não é possível agendar em uma data que já passou!';
	exit();
}

// Não aceita horário que já passou no dia de hoje
if($data == date('Y-m-d') and strtotime($data.' '.$hora) <= time()){
	echo 'Este horário já passou, escolha outro!';
	exit();
}

//validar horario
$query = $pdo->query("SELECT * FROM agendamentos where data = '$data' and hora = '$hora' and funcionario = '$funcionario'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Este horário não está disponível!';
	exit();
}

//Cadastrar o cliente caso não tenha cadastro
$query = $pdo->query("SELECT * FROM clientes where telefone LIKE '$telefone' ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	$query = $pdo->prepare("INSERT INTO clientes SET nome = :nome, telefone = :telefone, data_cad = curDate(), cartoes = '0', alertado = 'Não'");

	$query->bindValue(":nome", "$nome");
	$query->bindValue(":telefone", "$telefone");	
	$query->execute();
	$id_cliente = $pdo->lastInsertId();

}else{
	$id_cliente = $res[0]['id'];
}


if($id == ""){
	//marcar o agendamento
	$query = $pdo->prepare("INSERT INTO agendamentos SET funcionario = '$funcionario', cliente = '$id_cliente', hora = '$hora', data = '$data', usuario = '0', status = 'Aguardando', obs = :obs, data_lanc = curDate(), servico = '$servico', servicos = '$lista_servicos'");

	echo 'Agendado com Sucesso';
	
}else{
	//edito o agendamento
	$query = $pdo->prepare("UPDATE agendamentos SET funcionario = '$funcionario', hora = '$hora', data = '$data', usuario = '0', status = 'Aguardando', obs = :obs, data_lanc = curDate(), servico = '$servico', servicos = '$lista_servicos' where id = '$id'");

	echo 'Editado com Sucesso';
}

$query->bindValue(":obs", "$obs");
$query->execute();



?>