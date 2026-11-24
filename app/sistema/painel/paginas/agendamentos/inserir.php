<?php 
$tabela = 'agendamentos';
require_once("../../../conexao.php");
bb_csrf_check_request();

@session_start();
$usuario_logado = @$_SESSION['id'];

$cliente = $_POST['cliente'];
$data = $_POST['data'];
$hora = @$_POST['hora'];
$obs = $_POST['obs'];
$id = $_POST['id'];
$funcionario = $_POST['id_funcionario'];
$servico = $_POST['servico'];

if(@$hora == ""){
	echo 'Selecione um Horário antes de agendar!';
	exit();
}

$diasemana = array("Domingo", "Segunda-Feira", "Terça-Feira", "Quarta-Feira", "Quinta-Feira", "Sexta-Feira", "Sabado");
$diasemana_numero = date('w', strtotime($data));
$dia_procurado = $diasemana[$diasemana_numero];

//percorrer os dias da semana que ele trabalha
$query = $pdo->prepare("SELECT * FROM dias where funcionario = :v1 and dia = :v2");
$query->execute(array(':v1' => $funcionario, ':v2' => $dia_procurado));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
		echo 'Este Funcionário não trabalha neste Dia!';
	exit();
}



$dataF = implode('/', array_reverse(explode('-', $data)));
$horaF = date("H:i", strtotime($hora));

//validar horario
$query = $pdo->prepare("SELECT * FROM $tabela where data = :v1 and hora = :v2 and funcionario = :v3");
$query->execute(array(':v1' => $data, ':v2' => $hora, ':v3' => $funcionario));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0 and $res[0]['id'] != $id){
	echo 'Este horário não está disponível!';
	exit();
}


$query = $pdo->prepare("INSERT INTO $tabela SET funcionario = :funcionario, cliente = :cliente, hora = :hora, data = :data, usuario = :usuario, status = 'Aguardando', obs = :obs, data_lanc = curDate(), servico = :servico");
$query->execute(array(':funcionario' => $funcionario, ':cliente' => $cliente, ':hora' => $hora, ':data' => $data, ':usuario' => $usuario_logado, ':obs' => $obs, ':servico' => $servico));


echo 'Salvo com Sucesso'; 

?>
