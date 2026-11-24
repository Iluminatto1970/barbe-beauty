<?php 
require_once("../../../conexao.php");
@session_start();
$usuario = @$_SESSION['id'];

$funcionario = @$_SESSION['id'];
$data = @$_POST['data'];


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

?>
<div class="row">

	<?php 
	$query = $pdo->prepare("SELECT * FROM horarios where funcionario = :v1 ORDER BY horario asc");
	$query->execute(array(':v1' => $funcionario));
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);
	if($total_reg > 0){
		for($i=0; $i < $total_reg; $i++){
			foreach ($res[$i] as $key => $value){}
				$hora = $res[$i]['horario'];
				$horaF = date("H:i", strtotime($hora));


				//validar horario
$query2 = $pdo->prepare("SELECT * FROM agendamentos where data = :v1 and hora = :v2 and funcionario = :v3");
$query2->execute(array(':v1' => $data, ':v2' => $hora, ':v3' => $funcionario));
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$total_reg2 = @count($res2);
if($total_reg2 > 0){
	$hora_agendada = 'disabled';
	$texto_hora = 'text-danger';

}else{
	$hora_agendada = '';
	$texto_hora = '';
}


				?>

				<div class="col-md-2">
					<div class="form-check">
					  <input class="form-check-input" type="radio" name="hora" value="<?php echo $hora ?>" <?php echo $hora_agendada ?>>
					  <label class="form-check-label <?php echo $texto_hora ?>" for="flexRadioDefault1">
					    <?php echo $horaF ?>
					  </label>
					</div>
				</div> 

				<?php 
				
		}
	}
	?>


</div>
