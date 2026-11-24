<?php 
$tabela = 'agendamentos';
require_once("../../../conexao.php");
bb_csrf_check_request();
if (!bb_tabela_permitida($tabela)) { echo 'Tabela invalida'; exit(); }

@session_start();
$usuario_logado = @$_SESSION['id'];

$cliente = $_POST['cliente'];
$data = $_POST['data'];
$hora = @$_POST['hora'];
$obs = $_POST['obs'];
$id = $_POST['id'];
$funcionario = @$_SESSION['id'];
$servico = $_POST['servico'];

if(@$hora == ""){
	echo 'Selecione um Hora antes de agendar!';
	exit();
}

$diasemana = array("Domingo", "Segunda-Feira", "Terça-Feira", "Quarta-Feira", "Quinta-Feira", "Sexta-Feira", "Sabado");
$diasemana_numero = date('w', strtotime($data));
$dia_procurado = $diasemana[$diasemana_numero];

//percorrer os dias da semana que ele trabalha
$st = $pdo->prepare("SELECT * FROM dias where funcionario = :func and dia = :dia");
$st->execute(array(':func' => $funcionario, ':dia' => $dia_procurado));
$res = $st->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
		echo 'Este Funcionário não trabalha neste Dia!';
	exit();
}



$dataF = implode('/', array_reverse(explode('-', $data)));
$horaF = date("H:i", strtotime($hora));

//validar cpf
$st = $pdo->prepare("SELECT * FROM $tabela where data = :data and hora = :hora and funcionario = :func");
$st->execute(array(':data' => $data, ':hora' => $hora, ':func' => $funcionario));
$res = $st->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Este horário não está disponível!';
	exit();
}


$st = $pdo->prepare("INSERT INTO $tabela SET funcionario = :func, cliente = :cli, hora = :hora, data = :data, usuario = :usu, status = 'Aguardando', obs = :obs, data_lanc = curDate(), servico = :serv");

$st->execute(array(
	':func' => $funcionario,
	':cli' => $cliente,
	':hora' => $hora,
	':data' => $data,
	':usu' => $usuario_logado,
	':obs' => $obs,
	':serv' => $servico,
));

echo 'Salvo com Sucesso'; 

?>