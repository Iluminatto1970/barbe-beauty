<?php
require_once("../sistema/conexao.php");

// Modo demonstracao: a pagina de agendamento do site simula a reserva.
// Validamos data, horario e servico como no fluxo real, mas no final
// nada e gravado (nem cliente, nem agendamento).
$bb_demo = (isset($_POST['demo']) && (string) $_POST['demo'] === '1');

// Cota do plano gratuito: sem agendamentos quando o limite do mes acabou
// (a demonstracao nao consome cota, porque nao grava nada)
if (!$bb_demo && bb_quota_atingida($pdo)) {
	echo 'As reservas online estão temporariamente indisponíveis. Fale com o estabelecimento pelo WhatsApp.';
	exit();
}

$telefone = $_POST['telefone'];
$nome = $_POST['nome'];
$funcionario = $_POST['funcionario'];
$hora = @$_POST['hora'];
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

if($data < date('Y-m-d')){
	echo 'Não é possível agendar em uma data que já passou!';
	exit();
}

if($data == date('Y-m-d') and strtotime($data.' '.$hora) <= time()){
	echo 'Este horário já passou, escolha outro!';
	exit();
}

//validar horario
$query = $pdo->prepare("SELECT * FROM agendamentos where data = :data and hora = :hora and funcionario = :funcionario");
$query->execute(array(':data' => $data, ':hora' => $hora, ':funcionario' => $funcionario));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Este horário não está disponível!';
	exit();
}

// Demonstracao: encerra aqui, sem gravar cliente nem agendamento
if ($bb_demo) {
	echo 'Agendado com Sucesso';
	exit;
}

//Cadastrar o cliente caso não tenha cadastro
$query = $pdo->prepare("SELECT * FROM clientes where telefone LIKE :telefone");
$query->execute(array(':telefone' => $telefone));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
	$query = $pdo->prepare("INSERT INTO clientes SET nome = :nome, telefone = :telefone, data_cad = curDate(), cartoes = '0', alertado = 'Não'");
	$query->execute(array(':nome' => $nome, ':telefone' => $telefone));
	$id_cliente = $pdo->lastInsertId();
}else{
	$id_cliente = $res[0]['id'];
}

if($id == ""){
	$query = $pdo->prepare("INSERT INTO agendamentos SET funcionario = :funcionario, cliente = :id_cliente, hora = :hora, data = :data, usuario = '0', status = 'Aguardando', obs = :obs, data_lanc = curDate(), servico = :servico, servicos = :lista_servicos");
	$query->execute(array(':funcionario' => $funcionario, ':id_cliente' => $id_cliente, ':hora' => $hora, ':data' => $data, ':obs' => $obs, ':servico' => $servico, ':lista_servicos' => $lista_servicos));
	echo 'Agendado com Sucesso';
}else{
	$query = $pdo->prepare("UPDATE agendamentos SET funcionario = :funcionario, hora = :hora, data = :data, usuario = '0', status = 'Aguardando', obs = :obs, data_lanc = curDate(), servico = :servico, servicos = :lista_servicos where id = :id");
	$query->execute(array(':funcionario' => $funcionario, ':hora' => $hora, ':data' => $data, ':obs' => $obs, ':servico' => $servico, ':lista_servicos' => $lista_servicos, ':id' => $id));
	echo 'Editado com Sucesso';
}
?>
