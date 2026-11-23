<?php 
require_once("../sistema/conexao.php");
@session_start();
$usuario = @$_SESSION['id'];

$funcionario = @$_POST['funcionario'];
$data = @$_POST['data'];
$hora_rec = @$_POST['hora'];



if($funcionario == ""){
	
	exit();
}

if($data == ""){
	$data = date('Y-m-d');
}

// Não permite consultar datas passadas
if($data < date('Y-m-d')){
	echo 'Não há horários disponíveis nesta data. Escolha outro dia.';
	exit();
}


$diasemana = array("Domingo", "Segunda-Feira", "Terça-Feira", "Quarta-Feira", "Quinta-Feira", "Sexta-Feira", "Sabado");
$diasemana_numero = date('w', strtotime($data));
$dia_procurado = $diasemana[$diasemana_numero];

//percorrer os dias da semana que ele trabalha
$query = $pdo->query("SELECT * FROM dias where funcionario = '$funcionario' and dia = '$dia_procurado'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
		echo 'Este Funcionário não trabalha neste Dia!';
	exit();
}

?>
<div class="horarios-grid">
	<?php 
	$query = $pdo->query("SELECT * FROM horarios where funcionario = '$funcionario' ORDER BY horario asc");
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);
	$slots_exibidos = 0;
	if($total_reg > 0){
		for($i=0; $i < $total_reg; $i++){
			foreach ($res[$i] as $key => $value){}
				$hora = $res[$i]['horario'];
				$horaF = date("H:i", strtotime($hora));

				// Hoje: esconde horários que já passaram
				if(strtotime($data.' '.$hora) <= time()){
					continue;
				}

				//validar horario
				$query2 = $pdo->query("SELECT * FROM agendamentos where data = '$data' and hora = '$hora' and funcionario = '$funcionario'");
				$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
				$total_reg2 = @count($res2);
				$eh_edicao = ($hora_rec != "" && strtotime($hora_rec) == strtotime($hora));

				// Horário já reservado: aparece na grade, mas trancado (não pode ser escolhido)
				if($total_reg2 > 0 && !$eh_edicao){
					$status_ag = $res2[0]['status'];
					$rotulo = 'Confirmado';
					$classe_status = 'confirmado';
					if($status_ag == 'Aguardando'){
						$rotulo = 'Aguardando';
						$classe_status = 'aguardando';
					}else if($status_ag == 'Concluído' || $status_ag == 'Finalizado'){
						$rotulo = 'Atendido';
						$classe_status = 'concluido';
					}
					?>

					<div class="horario-card ocupado" data-hora="<?php echo $hora ?>" aria-disabled="true" title="Horário indisponível">
						<div class="horario-info">
							<h5><?php echo $horaF ?></h5>
							<p class="<?php echo $classe_status ?>"><?php echo $rotulo ?></p>
						</div>
						<div class="horario-check ocupado-check"><i class="fa fa-lock"></i></div>
					</div>

					<?php
					continue;
				}

				if($total_reg2 == 0 || $eh_edicao){
					$hora_agendada = '';
					$texto_hora = '';
					$selected_class = '';

					if(strtotime($hora_rec) == strtotime($hora)){
						$selected_class = 'selected';
					}

					$slots_exibidos++;
					?>

					<div class="horario-card <?php echo $selected_class ?>" onclick="selecionarHorario('<?php echo $hora ?>', this)" data-hora="<?php echo $hora ?>">
						<div class="horario-info">
							<h5><?php echo $horaF ?></h5>
							<p>Disponível</p>
						</div>
						<div class="horario-check"><i class="fa fa-check"></i></div>
					</div>

					<?php 
				}
			}
		}

		if($slots_exibidos == 0){
			echo '<p style="color:#fff; text-align:center; padding:15px;">Todos os horários desta data já estão reservados. Escolha outro dia ou profissional.</p>';
		}
	?>
	<input type="hidden" id="hora" name="hora" required>
</div>

<script>
function selecionarHorario(hora, elemento) {
	// Remove seleção anterior
	document.querySelectorAll('.horario-card').forEach(card => {
		card.classList.remove('selected');
	});
	
	// Adiciona seleção ao card clicado
	var card = elemento || (window.event ? window.event.currentTarget : null);
	if(card){ card.classList.add('selected'); }
	
	// Atualiza o campo hidden
	document.getElementById('hora').value = hora;
	
	// Atualiza o resumo
	updateSummary();
}
</script>