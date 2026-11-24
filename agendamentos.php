<?php 
require_once("cabecalho.php");
$data_atual = date('Y-m-d');
// Cor de destaque do segmento (barbearia = teal, salao = ameixa rosado)
$cor_tema = ($segmento == 'salao') ? '#7C5D67' : '#5a8e94';
?>
<style type="text/css">
	.sub_page .hero_area {
		min-height: auto;
	}

	/* Calendario de agendamento */
	.calendario-box{ background:#fff; border-radius:12px; padding:12px; box-shadow:0 4px 12px rgba(0,0,0,.12); }
	.calendario-topo{ display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
	.calendario-topo strong{ font-size:16px; color:#2c3e50; text-transform:capitalize; }
	.calendario-nav{ border:0; background:<?php echo $cor_tema ?>; color:#fff; width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:13px; }
	.calendario-nav:disabled{ background:#cfd8dc; cursor:not-allowed; }
	.calendario-semana,.calendario-dias{ display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
	.calendario-semana span{ text-align:center; font-size:11px; font-weight:bold; color:#6b7c85; padding:4px 0; }
	.calendario-dia{ border:0; background:#f1f5f6; color:#2c3e50; border-radius:8px; padding:9px 0; font-size:14px; cursor:pointer; }
	.calendario-dia:hover:not(:disabled){ background:#d7e7e9; }
	.calendario-dia.hoje{ box-shadow: inset 0 0 0 2px <?php echo $cor_tema ?>; }
	.calendario-dia.selecionado{ background:<?php echo $cor_tema ?>; color:#fff; font-weight:bold; }
	.calendario-dia:disabled{ background:#fff; color:#c3cdd1; cursor:not-allowed; text-decoration:line-through; }
	.calendario-dia.passado{ background:#eceff1; color:#90a4ae; text-decoration:line-through; cursor:not-allowed; }
	.calendario-dia.fechado{ background:#f3f6f8; color:#9aa7ae; text-decoration:none; cursor:not-allowed; }
	.calendario-dia.lotado{ background:#fdecea; color:#c0392b; text-decoration:line-through; font-weight:600; cursor:not-allowed; }
	.calendario-dia.vazio{ visibility:hidden; }
	.calendario-legenda{ font-size:13px; color:#fff; text-align:center; margin:8px 0 6px 0; }
	.calendario-obs{ font-size:12px; color:#e6e6e6; text-align:center; margin:0 0 12px 0; }
	.calendario-dia.com-agendamentos{ position:relative; }
	.calendario-dia .cal-dot{ position:absolute; left:50%; bottom:3px; transform:translateX(-50%); width:5px; height:5px; border-radius:50%; background:#f0ad4e; }
	.calendario-dia .cal-dot.confirmado{ background:#2ecc71; }
	.cal-dot-legenda{ display:inline-block; width:8px; height:8px; border-radius:50%; margin:0 5px 0 14px; vertical-align:middle; background:#f0ad4e; }
	.cal-dot-legenda.confirmado{ background:#2ecc71; }
	.cal-legenda-cor{ display:inline-block; width:10px; height:10px; border-radius:3px; margin:0 5px 0 14px; vertical-align:middle; }
	.cal-legenda-cor.passado{ background:#eceff1; border:1px solid #cfd8dc; }
	.cal-legenda-cor.lotado{ background:#fdecea; border:1px solid #f0b9b4; }

	/* Botao de envio do agendamento no WhatsApp */
	.btn-whatsapp{ display:inline-block; margin-top:14px; background:#25d366; color:#fff !important; padding:12px 22px; border-radius:30px; font-weight:bold; text-decoration:none !important; font-size:15px; box-shadow:0 4px 12px rgba(0,0,0,.2); }
	.btn-whatsapp:hover{ background:#1ebe5b; color:#fff !important; }
</style>

</div>

<div class="footer_section" style="background: <?php echo $cor_tema ?>; ">
	<div class="container" >
		<div class="footer_content " >
			<!-- Progress Steps -->
			<div class="steps-container" style="margin-bottom: 30px;">
				<div class="step-progress">
					<div class="step active" id="step-1">
						<div class="step-number">1</div>
						<div class="step-title">Dados</div>
					</div>
					<div class="step-line"></div>
					<div class="step" id="step-2">
						<div class="step-number">2</div>
						<div class="step-title">Data</div>
					</div>
					<div class="step-line"></div>
					<div class="step" id="step-3">
						<div class="step-number">3</div>
						<div class="step-title">Horário</div>
					</div>
					<div class="step-line"></div>
					<div class="step" id="step-4">
						<div class="step-number">4</div>
						<div class="step-title">Serviço</div>
					</div>
					<div class="step-line"></div>
					<div class="step" id="step-5">
						<div class="step-number">5</div>
						<div class="step-title">Resumo</div>
					</div>
				</div>
			</div>

			<form id="form-agenda" method="post" style="margin-top: -25px !important">
			<div class="footer_form footer-col">
				
				<!-- Step 1: Dados Pessoais -->
				<div class="step-content" id="step-content-1">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Seus Dados</h4>
					<div class="form-group">
						<input class="form-control" type="text" name="telefone" id="telefone" placeholder="Seu Telefone DDD + número" required />
					</div>
					<div class="form-group">
						<input class="form-control" type="text" name="nome" id="nome" placeholder="Seu Nome" required />
					</div>
					<div class="step-buttons">
						<button type="button" class="btn-next" onclick="nextStep(1)">Próximo</button>
					</div>
				</div>

				<!-- Step 2: Data e Profissional -->
				<div class="step-content" id="step-content-2" style="display: none;">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Data e Profissional</h4>
					<div class="form-group">
						<?php
						// Dias da semana em que cada profissional atende (para bloquear no calendario)
						$dias_prof = array();
						$q_dias = $pdo->query("SELECT funcionario, dia FROM dias");
						foreach($q_dias->fetchAll(PDO::FETCH_ASSOC) as $linha_dia){
							$dias_prof[(string)$linha_dia['funcionario']][] = $linha_dia['dia'];
						}

						// Horarios de cada profissional e horarios ja ocupados (para mostrar trancados no calendario)
						$horarios_prof = array();
						$q_hor = $pdo->query("SELECT funcionario, horario FROM horarios ORDER BY horario asc");
						foreach($q_hor->fetchAll(PDO::FETCH_ASSOC) as $linha_h){
							$horarios_prof[(string)$linha_h['funcionario']][] = substr($linha_h['horario'], 0, 5);
						}
						$agenda_ocupada = array();
						$q_ag = $pdo->query("SELECT funcionario, data, hora, status FROM agendamentos WHERE data >= curDate() and status in ('Aguardando', 'Agendado', 'Concluído')");
						foreach($q_ag->fetchAll(PDO::FETCH_ASSOC) as $linha_ag){
							$agenda_ocupada[(string)$linha_ag['funcionario']][$linha_ag['data']][] = array('hora' => substr($linha_ag['hora'], 0, 5), 'status' => $linha_ag['status']);
						}
						?>
						<script>var DIAS_PROFISSIONAIS = <?php echo json_encode($dias_prof); ?>; var HORARIOS_PROFISSIONAIS = <?php echo json_encode($horarios_prof); ?>; var AGENDA_OCUPADA = <?php echo json_encode($agenda_ocupada); ?>;</script>

						<label style="color:#fff; font-weight:600; display:block; margin-bottom:6px;">Escolha o dia</label>
						<div class="calendario-box">
							<div class="calendario-topo">
								<button type="button" class="calendario-nav" id="cal-prev" onclick="mudarMesCalendario(-1)"><i class="fa fa-chevron-left"></i></button>
								<strong id="cal-titulo"></strong>
								<button type="button" class="calendario-nav" id="cal-next" onclick="mudarMesCalendario(1)"><i class="fa fa-chevron-right"></i></button>
							</div>
							<div class="calendario-semana">
								<span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
							</div>
							<div class="calendario-dias" id="cal-dias"></div>
						</div>
						<div class="calendario-legenda" id="cal-legenda">Selecione um dia disponível</div>
						<div class="calendario-obs"><span class="cal-dot-legenda"></span>Aguardando confirmação<span class="cal-dot-legenda confirmado"></span>Confirmado</div>
						<div class="calendario-obs"><span class="cal-legenda-cor passado"></span>Dia encerrado<span class="cal-legenda-cor lotado"></span>Sem horário livre</div>
						<input type="hidden" name="data" id="data" value="<?php echo $data_atual ?>" />
					</div>
					<div class="form-group">
						<div class="professionals-grid">
							<?php 
							$query = $pdo->query("SELECT * FROM usuarios where atendimento = 'Sim' " . bb_filtro_profissionais() . " ORDER BY id desc");
							$res = $query->fetchAll(PDO::FETCH_ASSOC);
							$total_reg = @count($res);
							if($total_reg > 0){
								for($i=0; $i < $total_reg; $i++){
									$foto = $res[$i]['foto'];
									if($foto == "" || $foto == "sem-foto.jpg"){
										$foto = "sistema/img/" . $logo_sistema;
									} else {
										$foto = "sistema/painel/img/perfil/".$foto;
									}
									echo '<div class="professional-card" onclick="selecionarProfissional('.$res[$i]['id'].', \''.addslashes($res[$i]['nome']).'\')" data-id="'.$res[$i]['id'].'">';
									echo '<div class="professional-photo">';
									echo '<img src="'.$foto.'" alt="'.$res[$i]['nome'].'" onerror="this.src=\'sistema/img/<?php echo $logo_sistema ?>\'">';
									echo '</div>';
									echo '<div class="professional-info">';
									echo '<h5>'.$res[$i]['nome'].'</h5>';
									$cargo_exibir = $res[$i]['nivel'];
									if (isset($segmento) && $segmento == 'salao' && in_array(mb_strtolower($cargo_exibir, 'UTF-8'), array('barbeiro', 'barbeira'))) {
										$cargo_exibir = 'Profissional';
									}
									echo '<p>'.$cargo_exibir.'</p>';
									echo '</div>';
									echo '<div class="professional-check"><i class="fa fa-check"></i></div>';
									echo '</div>';
								}
							}
							?>
						</div>
						<input type="hidden" id="funcionario" name="funcionario" required>
					</div>
					<div class="step-buttons">
						<button type="button" class="btn-prev" onclick="prevStep(2)">Anterior</button>
						<button type="button" class="btn-next" onclick="nextStep(2)">Próximo</button>
					</div>
				</div>

				<!-- Step 3: Horário -->
				<div class="step-content" id="step-content-3" style="display: none;">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Horário Disponível</h4>
					<div class="form-group"> 									
						<div id="listar-horarios">
							
						</div>
					</div>
					<div class="step-buttons">
						<button type="button" class="btn-prev" onclick="prevStep(3)">Anterior</button>
						<button type="button" class="btn-next" onclick="nextStep(3)">Próximo</button>
					</div>
				</div>

				<!-- Step 4: Serviço -->
				<div class="step-content" id="step-content-4" style="display: none;">
					<h4 style="color: white; text-align: center; margin-bottom: 6px;">Serviço Desejado</h4>
					<p style="color: #ddd; text-align: center; margin-bottom: 20px; font-size: 14px;">Escolha um ou mais serviços.</p>
					<div class="form-group">
						<div class="services-grid">
							<?php 
							$query = $pdo->prepare("SELECT * FROM servicos where segmento = :v1 ORDER BY nome asc");
							$query->execute(array(':v1' => $segmento));
							$res = $query->fetchAll(PDO::FETCH_ASSOC);
							$total_reg = @count($res);
							if($total_reg > 0){
								for($i=0; $i < $total_reg; $i++){
									$foto = $res[$i]['foto'];
									if($foto == "" || $foto == "sem-foto.jpg"){
										$foto = "sistema/img/" . $logo_sistema;
									} else {
										$foto = "sistema/painel/img/servicos/".$foto;
									}
									$valor = $res[$i]['valor'];
									$valorF = number_format($valor, 2, ',', '.');
									echo '<div class="service-card" onclick="alternarServico(this)" data-id="'.$res[$i]['id'].'" data-nome="'.htmlspecialchars($res[$i]['nome'], ENT_QUOTES).'" data-valor="'.$valor.'">';
									echo '<div class="service-photo">';
									echo '<img src="'.$foto.'" alt="'.$res[$i]['nome'].'" onerror="this.src=\'sistema/img/<?php echo $logo_sistema ?>\'">';
									echo '</div>';
									echo '<div class="service-info">';
									echo '<h5>'.$res[$i]['nome'].'</h5>';
									echo '<p>R$ '.$valorF.'</p>';
									echo '</div>';
									echo '<div class="service-check"><i class="fa fa-check"></i></div>';
									echo '</div>';
								}
							}
							?>
						</div>
						<div id="total-servicos" style="color:#fff; text-align:center; margin-top:10px; font-weight:600; font-size:15px;"></div>
						<input type="hidden" id="servico" name="servico" required>
					</div>
					<div class="step-buttons">
						<button type="button" class="btn-prev" onclick="prevStep(4)">Anterior</button>
						<button type="button" class="btn-next" onclick="nextStep(4)">Próximo</button>
					</div>
				</div>

				<!-- Step 5: Confirmação -->
				<div class="step-content" id="step-content-5" style="display: none;">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Resumo do Agendamento</h4>
					
					<div class="summary-box" style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin-bottom: 25px; color: white;">
						<h5 style="margin-bottom: 15px; color: #ffffff; font-weight: bold;">📋 Resumo do Agendamento:</h5>
						<div id="summary-content"></div>
					</div>

					<div class="form-group" style="margin-bottom: 25px;"> 						
						<input maxlength="100" type="text" class="form-control" name="obs" id="obs" placeholder="💬 Observações caso exista alguma..." style="border-radius: 8px; padding: 12px;">
					</div>

					<!-- Botões para novo agendamento -->
					<div class="confirmation-buttons" id="new-appointment-buttons">
						<div class="button-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
							<button type="button" class="btn-prev-final" onclick="prevStep(5)" style="flex: 1;">← Voltar</button>
							<button onclick="salvar()" class="btn-confirm" type="submit" style="flex: 2;">
								<span id='botao_salvar'>✅ Confirmar Agendamento</span>
							</button>
						</div>
					</div>


				</div>

                <br><br>
               <!-- Mensagem de Sucesso Melhorada -->
               <div id="mensagem" class="mensagem-container" align="center"></div>			

               <input type="text" id="data_oculta" style="display: none">	
                <input type="hidden" id="id" name="id">	
                 <input type="hidden" id="hora_rec" nome="hora_rec">	
                  <input type="hidden" id="nome_func" nome="nome_func">	
                  <input type="hidden" id="data_rec" nome="data_rec">	
                    <input type="hidden" id="nome_serv" nome="nome_serv">			
				

			</div>



		</form>







		</div>


	</div>


</div>




<?php require_once("rodape.php") ?>













<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Função para criar efeito confetti
function criarConfetti() {
	// Criar container de confetti se não existir
	let confettiContainer = document.querySelector('.confetti-container');
	if (!confettiContainer) {
		confettiContainer = document.createElement('div');
		confettiContainer.className = 'confetti-container';
		document.body.appendChild(confettiContainer);
	}
	
	// Limpar confetti anterior
	const existingConfetti = confettiContainer.querySelectorAll('.confetti');
	existingConfetti.forEach(c => c.remove());
	
	// Criar novo confetti
	for (let i = 0; i < 50; i++) {
		const confetti = document.createElement('div');
		confetti.className = 'confetti';
		
		// Cores aleatórias
		const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3', '#54a0ff', '#5f27cd', '#00d2d3', '#ffd700'];
		confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
		
		// Posição e timing aleatórios
		confetti.style.left = Math.random() * 100 + '%';
		confetti.style.animationDelay = Math.random() * 2 + 's';
		confetti.style.animationDuration = (Math.random() * 2 + 3) + 's';
		
		// Tamanho aleatório
		const size = Math.random() * 8 + 6; // 6px a 14px
		confetti.style.width = size + 'px';
		confetti.style.height = size + 'px';
		
		confettiContainer.appendChild(confetti);
		
		// Remover após animação
		setTimeout(() => {
			if (confetti.parentNode) {
				confetti.parentNode.removeChild(confetti);
			}
		}, 6000);
	}
	
	// Remover container após todas as animações
	setTimeout(() => {
		if (confettiContainer.parentNode) {
			confettiContainer.parentNode.removeChild(confettiContainer);
		}
	}, 8000);
}
</script>

<style type="text/css">
	.select2-selection__rendered {
		line-height: 45px !important;
		font-size:16px !important;
		color:#000 !important;

	}

	.select2-selection {
		height: 45px !important;
		font-size:16px !important;
		color:#000 !important;
		border-radius: 25px !important;

	}

	/* Form Controls Styling */
	.form-control {
		border-radius: 25px !important;
		padding: 12px 20px !important;
		border: 2px solid rgba(255,255,255,0.3) !important;
		background: rgba(255,255,255,0.1) !important;
		color: white !important;
		font-size: 16px !important;
		transition: all 0.3s ease !important;
	}

	.form-control:focus {
		border-color: var(--dourado) !important;
		background: rgba(255,255,255,0.2) !important;
		box-shadow: 0 0 10px rgba(212, 175, 55, 0.32) !important;
		color: white !important;
	}

	.form-control::placeholder {
		color: rgba(255,255,255,0.7) !important;
	}

	/* Select2 Dropdown Styling */
	.select2-dropdown {
		border-radius: 15px !important;
		border: 2px solid var(--dourado) !important;
	}

	.select2-container--default .select2-selection--single {
		border-radius: 25px !important;
		border: 2px solid rgba(255,255,255,0.3) !important;
		background: rgba(255,255,255,0.1) !important;
		height: 48px !important;
		line-height: 44px !important;
	}

	.select2-container--default .select2-selection--single:focus {
		border-color: var(--dourado) !important;
	}

	.select2-container--default .select2-selection--single .select2-selection__rendered {
		color: white !important;
		padding-left: 20px !important;
	}

	.select2-container--default .select2-selection--single .select2-selection__arrow {
		height: 44px !important;
		right: 15px !important;
	}

	/* Steps Styling */
	.steps-container {
		padding: 20px 0;
	}

	.step-progress {
		display: flex;
		align-items: center;
		justify-content: center;
		flex-wrap: wrap;
		gap: 10px;
	}

	.step {
		display: flex;
		flex-direction: column;
		align-items: center;
		text-align: center;
		min-width: 80px;
		cursor: pointer;
		transition: all 0.3s ease;
	}

	.step:hover .step-number {
		transform: scale(1.1);
	}

	.step.completed {
		cursor: pointer;
	}

	.step.completed:hover .step-number {
		background: #138496;
	}

	.step-number {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: rgba(255,255,255,0.3);
		color: white;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: bold;
		margin-bottom: 8px;
		transition: all 0.3s ease;
	}

	.step-title {
		color: white;
		font-size: 12px;
		font-weight: 500;
	}

	.step.active .step-number {
		background: #28a745;
		box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
	}

	.step.completed .step-number {
		background: #17a2b8;
	}

	.step-line {
		width: 30px;
		height: 2px;
		background: rgba(255,255,255,0.3);
		margin: 0 5px;
		margin-top: -25px;
	}

	.step-line.completed {
		background: #17a2b8;
	}

	/* Step Content */
	.step-content {
		animation: fadeIn 0.5s ease-in-out;
	}

	@keyframes fadeIn {
		from { opacity: 0; transform: translateX(20px); }
		to { opacity: 1; transform: translateX(0); }
	}

	/* Step Buttons */
	.step-buttons {
		display: flex;
		justify-content: space-between;
		margin-top: 20px;
		gap: 10px;
	}

	.btn-prev, .btn-next {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(45deg, var(--dourado), var(--dourado-claro));
		color: var(--dourado-escuro);
		text-decoration: none;
		border-radius: 25px !important;
		font-weight: 600;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
		border: none;
		cursor: pointer;
		flex: 1;
		max-width: 150px;
		min-height: 48px;
		text-align: center;
		box-sizing: border-box;
	}

	.btn-prev::before, .btn-next::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(45deg, #0d0d0d, #8a6d1f);
		transition: left 0.3s ease;
		z-index: -1;
	}

	.btn-prev:hover, .btn-next:hover {
		color: white !important;
		text-decoration: none;
		transform: translateY(-2px);
	}

	.btn-prev:hover::before, .btn-next:hover::before {
		left: 0;
	}

	.btn-next:disabled {
		background: #6c757d;
		cursor: not-allowed;
		transform: none;
	}

	.btn-next:disabled::before {
		display: none;
	}

	/* Summary Box */
	.summary-box {
		border-left: 4px solid #28a745;
		border-radius: 15px !important;
		background: rgba(255,255,255,0.1) !important;
		backdrop-filter: blur(10px);
	}

	/* Modal Styling */
	.modal-content {
		border-radius: 20px !important;
		border: none !important;
		box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
	}

	.modal-header {
		border-bottom: 1px solid rgba(0,0,0,0.1) !important;
		border-radius: 20px 20px 0 0 !important;
	}

	.modal-footer {
		border-top: 1px solid rgba(0,0,0,0.1) !important;
		border-radius: 0 0 20px 20px !important;
	}

	/* Horários Grid Styling */
	.horarios-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
		gap: 15px;
		margin: 20px 0;
	}

	.horario-card {
		background: rgba(255,255,255,0.1);
		border: 2px solid rgba(255,255,255,0.3);
		border-radius: 15px;
		padding: 20px;
		text-align: center;
		cursor: pointer;
		transition: all 0.3s ease;
		position: relative;
		backdrop-filter: blur(10px);
		min-height: 80px;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
	}

	.horario-card:hover {
		transform: translateY(-5px);
		border-color: var(--dourado);
		box-shadow: 0 10px 25px rgba(212, 175, 55, 0.32);
		background: rgba(255,255,255,0.2);
	}

	.horario-card.selected {
		border-color: #28a745;
		background: rgba(40, 167, 69, 0.2);
		box-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
	}

	.horario-info h5 {
		color: white;
		margin: 0 0 5px 0;
		font-size: 18px;
		font-weight: 600;
	}

	.horario-info p {
		color: rgba(255,255,255,0.8);
		margin: 0;
		font-size: 12px;
	}

	.horario-check {
		position: absolute;
		top: 8px;
		right: 8px;
		width: 20px;
		height: 20px;
		background: #28a745;
		border-radius: 50%;
		display: none;
		align-items: center;
		justify-content: center;
		color: white;
		font-size: 10px;
	}

	.horario-card.selected .horario-check {
		display: flex;
	}

	/* Horario ja reservado: aparece na grade, mas trancado */
	.horario-card.ocupado {
		cursor: not-allowed;
		opacity: .6;
		background: rgba(0,0,0,0.28);
		border-color: rgba(255,255,255,0.18);
	}
	.horario-card.ocupado:hover {
		transform: none;
		box-shadow: none;
		border-color: rgba(255,255,255,0.18);
		background: rgba(0,0,0,0.28);
	}
	.horario-card.ocupado .horario-info p { font-weight: 600; }
	.horario-card.ocupado .horario-info p.aguardando { color: #f0ad4e; }
	.horario-card.ocupado .horario-info p.confirmado { color: #2ecc71; }
	.horario-card.ocupado .horario-info p.concluido { color: #bbbbbb; }
	.horario-card.ocupado .ocupado-check {
		display: flex;
		background: rgba(255,255,255,0.28);
	}

	/* Legacy support for old horario-item class */
	.horario-item {
		border-radius: 15px !important;
		transition: all 0.3s ease;
	}

	.horario-item:hover {
		transform: translateY(-2px);
		box-shadow: 0 5px 15px rgba(0,0,0,0.2);
	}

	.summary-item {
		margin-bottom: 8px;
		padding: 5px 0;
		border-bottom: 1px solid rgba(255,255,255,0.1);
	}

	.summary-item:last-child {
		border-bottom: none;
	}

	.summary-label {
		font-weight: bold;
		margin-right: 10px;
	}

	/* Responsive */
	@media (max-width: 768px) {
		.steps-container {
			padding: 15px 0;
		}

		.step-progress {
			flex-direction: row;
			justify-content: space-between;
			gap: 5px;
			overflow-x: auto;
			padding: 10px 5px;
		}

		.step {
			min-width: 60px;
			flex-shrink: 0;
		}

		.step-number {
			width: 35px;
			height: 35px;
			font-size: 14px;
			margin-bottom: 5px;
		}

		.step-title {
			font-size: 10px;
			line-height: 1.2;
			max-width: 60px;
			word-wrap: break-word;
		}

		.step-line {
			width: 15px;
			height: 2px;
			margin: 0 2px;
			margin-top: -20px;
			flex-shrink: 0;
		}

		.step-buttons {
			flex-direction: column;
			gap: 15px;
		}

		.btn-prev, .btn-next {
			max-width: none;
			padding: 15px 20px;
			font-size: 16px;
		}

		.step-content h4 {
			font-size: 1.3rem !important;
			margin-bottom: 25px !important;
		}

		.form-group {
			margin-bottom: 20px;
		}

		.form-control {
			font-size: 16px !important;
			padding: 15px 20px !important;
		}
	}

	@media (max-width: 576px) {
		.steps-container {
			padding: 10px 0;
		}

		.step-progress {
			gap: 3px;
			padding: 5px;
		}

		.step {
			min-width: 50px;
		}

		.step-number {
			width: 30px;
			height: 30px;
			font-size: 12px;
		}

		.step-title {
			font-size: 9px;
			max-width: 50px;
		}

		.step-line {
			width: 10px;
			margin-top: -18px;
		}

		.step-content h4 {
			font-size: 1.1rem !important;
		}

		.btn-prev, .btn-next {
			padding: 12px 15px;
			font-size: 14px;
		}
	}

	@media (max-width: 480px) {
		.step-progress {
			gap: 2px;
		}

		.step {
			min-width: 45px;
		}

		.step-number {
			width: 28px;
			height: 28px;
			font-size: 11px;
		}

		.step-title {
			font-size: 8px;
			max-width: 45px;
		}

		.step-line {
			width: 8px;
			margin-top: -16px;
		}

		.professionals-grid {
			grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
			gap: 6px;
		}

		.professional-card {
			padding: 10px;
		}

		.professional-photo {
			width: 45px;
			height: 45px;
			margin-bottom: 6px;
		}

		.professional-info h5 {
			font-size: 11px;
		}

		.professional-info p {
			font-size: 9px;
		}
	}

	/* Final Step Buttons */
	.btn-prev-final {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(45deg, var(--dourado), var(--dourado-claro));
		color: var(--dourado-escuro);
		text-decoration: none;
		border-radius: 25px !important;
		font-weight: 600;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
		border: none;
		cursor: pointer;
		font-size: 14px;
		min-height: 48px;
		text-align: center;
		box-sizing: border-box;
	}

	.btn-prev-final::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(45deg, #0d0d0d, #8a6d1f);
		transition: left 0.3s ease;
		z-index: -1;
	}

	.btn-prev-final:hover {
		color: white !important;
		text-decoration: none;
		transform: translateY(-2px);
	}

	.btn-prev-final:hover::before {
		left: 0;
	}

	.btn-confirm {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(45deg, var(--dourado), var(--dourado-claro));
		color: var(--dourado-escuro);
		text-decoration: none;
		border-radius: 25px !important;
		font-weight: 600;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
		border: none;
		cursor: pointer;
		font-size: 16px;
		min-height: 48px;
		text-align: center;
		box-sizing: border-box;
	}

	.btn-confirm::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(45deg, #0d0d0d, #8a6d1f);
		transition: left 0.3s ease;
		z-index: -1;
	}

	.btn-confirm:hover {
		color: white !important;
		text-decoration: none;
		transform: translateY(-2px);
	}

	.btn-confirm:hover::before {
		left: 0;
	}

	/* Professional Cards Styling */
	.professionals-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 15px;
		margin: 20px 0;
	}

	.professional-card {
		background: rgba(255,255,255,0.1);
		border: 2px solid rgba(255,255,255,0.3);
		border-radius: 15px;
		padding: 20px;
		text-align: center;
		cursor: pointer;
		transition: all 0.3s ease;
		position: relative;
		backdrop-filter: blur(10px);
	}

	.professional-card:hover {
		transform: translateY(-5px);
		border-color: var(--dourado);
		box-shadow: 0 10px 25px rgba(212, 175, 55, 0.32);
		background: rgba(255,255,255,0.2);
	}

	.professional-card.selected {
		border-color: #28a745;
		background: rgba(40, 167, 69, 0.2);
		box-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
	}

	.professional-photo {
		width: 80px;
		height: 80px;
		margin: 0 auto 15px;
		border-radius: 50%;
		overflow: hidden;
		border: 3px solid rgba(255,255,255,0.3);
		transition: all 0.3s ease;
	}

	.professional-card:hover .professional-photo {
		border-color: var(--dourado);
	}

	.professional-card.selected .professional-photo {
		border-color: #28a745;
	}

	.professional-photo img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.professional-info h5 {
		color: white;
		margin: 0 0 5px 0;
		font-size: 16px;
		font-weight: 600;
	}

	.professional-info p {
		color: rgba(255,255,255,0.8);
		margin: 0;
		font-size: 14px;
	}

	.professional-check {
		position: absolute;
		top: 10px;
		right: 10px;
		width: 25px;
		height: 25px;
		background: #28a745;
		border-radius: 50%;
		display: none;
		align-items: center;
		justify-content: center;
		color: white;
		font-size: 12px;
	}

	.professional-card.selected .professional-check {
		display: flex;
	}

	/* Mensagem de Sucesso Estilizada */
	.mensagem-container {
		margin: 20px 0;
		padding: 0;
		position: relative;
	}

	.mensagem-sucesso {
		background: linear-gradient(135deg, #28a745, #20c997);
		color: white;
		border: none;
		border-radius: 20px;
		padding: 25px 30px;
		margin: 20px auto;
		max-width: 500px;
		box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
		font-size: 18px;
		font-weight: 600;
		text-align: center;
		position: relative;
		overflow: hidden;
		transform: scale(0);
		animation: successPulse 0.6s ease-out forwards;
		backdrop-filter: blur(10px);
	}

	.mensagem-sucesso::before {
		content: '';
		position: absolute;
		top: -50%;
		left: -50%;
		width: 200%;
		height: 200%;
		background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
		transform: rotate(45deg);
		animation: shine 2s infinite;
	}

	.mensagem-sucesso .icone-sucesso {
		display: inline-block;
		margin-right: 12px;
		font-size: 24px;
		animation: bounce 1s infinite alternate;
	}

	.mensagem-sucesso .texto-sucesso {
		display: inline-block;
		vertical-align: middle;
		position: relative;
		z-index: 2;
	}

	@keyframes successPulse {
		0% {
			transform: scale(0);
			opacity: 0;
		}
		50% {
			transform: scale(1.1);
			opacity: 1;
		}
		100% {
			transform: scale(1);
			opacity: 1;
		}
	}

	@keyframes bounce {
		0% {
			transform: translateY(0);
		}
		100% {
			transform: translateY(-5px);
		}
	}

	@keyframes shine {
		0% {
			transform: translateX(-100%) translateY(-100%) rotate(45deg);
		}
		100% {
			transform: translateX(100%) translateY(100%) rotate(45deg);
		}
	}

	.mensagem-sucesso:hover {
		transform: scale(1.05);
		box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
		transition: all 0.3s ease;
	}

	/* Mensagem de Erro Estilizada */
	.mensagem-erro {
		background: linear-gradient(135deg, #dc3545, #e74c3c);
		color: white;
		border: none;
		border-radius: 20px;
		padding: 25px 30px;
		margin: 20px auto;
		max-width: 500px;
		box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
		font-size: 18px;
		font-weight: 600;
		text-align: center;
		position: relative;
		overflow: hidden;
		transform: scale(0);
		animation: errorShake 0.6s ease-out forwards;
		backdrop-filter: blur(10px);
	}

	.mensagem-erro .icone-erro {
		display: inline-block;
		margin-right: 12px;
		font-size: 24px;
		animation: shake 0.5s infinite;
	}

	.mensagem-erro .texto-erro {
		display: inline-block;
		vertical-align: middle;
		position: relative;
		z-index: 2;
	}

	@keyframes errorShake {
		0% {
			transform: scale(0) translateX(0);
			opacity: 0;
		}
		50% {
			transform: scale(1.1) translateX(-5px);
			opacity: 1;
		}
		100% {
			transform: scale(1) translateX(0);
			opacity: 1;
		}
	}

	@keyframes shake {
		0%, 100% {
			transform: translateX(0);
		}
		25% {
			transform: translateX(-3px);
		}
		75% {
			transform: translateX(3px);
		}
	}

	/* Confetti Container */
	.confetti-container {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		pointer-events: none;
		z-index: 9999;
		overflow: hidden;
	}

	/* Confetti Animation */
	.confetti {
		position: absolute;
		top: -20px;
		width: 10px;
		height: 10px;
		background: #ffd700;
		animation: confetti-fall 4s linear forwards;
		pointer-events: none;
		border-radius: 50%;
		box-shadow: 0 0 6px rgba(0,0,0,0.3);
	}

	@keyframes confetti-fall {
		0% {
			transform: translateY(-20px) rotate(0deg);
			opacity: 1;
		}
		10% {
			opacity: 1;
		}
		90% {
			opacity: 0.8;
		}
		100% {
			transform: translateY(100vh) rotate(720deg);
			opacity: 0;
		}
	}

	/* Responsive para botões finais */
	@media (max-width: 768px) {
		.btn-prev-final, .btn-confirm {
			padding: 15px 25px;
			font-size: 16px;
			min-height: 50px;
			width: 100%;
			margin-bottom: 10px;
		}

		.professionals-grid {
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 10px;
		}

		.professional-card {
			padding: 15px;
		}

		.professional-photo {
			width: 60px;
			height: 60px;
			margin-bottom: 10px;
		}

		.professional-info h5 {
			font-size: 14px;
		}

		.professional-info p {
			font-size: 12px;
		}

		.mensagem-sucesso {
			padding: 20px 25px;
			font-size: 16px;
			margin: 15px 10px;
		}

		.mensagem-sucesso .icone-sucesso {
			font-size: 20px;
			margin-right: 8px;
		}

		.mensagem-erro {
			padding: 20px 25px;
			font-size: 16px;
			margin: 15px 10px;
		}

		.mensagem-erro .icone-erro {
			font-size: 20px;
			margin-right: 8px;
		}

		/* Horários Grid Mobile */
		.horarios-grid {
			grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
			gap: 8px;
			margin: 15px 0;
		}

		.horario-card {
			padding: 15px 10px;
			min-height: 60px;
		}

		.horario-info h5 {
			font-size: 16px;
		}

		.horario-info p {
			font-size: 11px;
		}

		.horario-check {
			width: 18px;
			height: 18px;
			font-size: 9px;
			top: 6px;
			right: 6px;
		}

		/* Legacy support */
		.horario-item {
			padding: 12px 8px !important;
			font-size: 14px !important;
			min-height: 45px;
			border-radius: 10px !important;
		}

		/* Summary Box Mobile */
		.summary-box {
			margin: 15px 0 !important;
			padding: 20px !important;
			border-radius: 10px !important;
		}

		.summary-item {
			margin-bottom: 12px;
			padding: 10px 0;
			font-size: 16px;
		}

		.summary-label {
			display: block;
			margin-bottom: 5px;
			font-size: 14px;
			color: rgba(255,255,255,0.8);
		}

		/* Select2 Mobile */
		.select2-container--default .select2-selection--single {
			height: 50px !important;
			line-height: 46px !important;
			font-size: 16px !important;
		}
	}

	@media (max-width: 576px) {
		.btn-prev-final, .btn-confirm {
			padding: 12px 20px;
			font-size: 14px;
			min-height: 45px;
		}

		.professionals-grid, .services-grid {
			grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
			gap: 8px;
		}

		.professional-card, .service-card {
			padding: 12px;
		}

		.professional-photo, .service-photo {
			width: 50px;
			height: 50px;
			margin-bottom: 8px;
		}

		.professional-info h5, .service-info h5 {
			font-size: 12px;
		}

		.professional-info p, .service-info p {
			font-size: 10px;
		}

		.professional-check, .service-check {
			width: 20px;
			height: 20px;
			font-size: 10px;
			top: 5px;
			right: 5px;
		}

		.horarios-grid {
			grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
			gap: 6px;
		}

		.horario-card {
			padding: 12px 8px;
			min-height: 50px;
		}

		.horario-info h5 {
			font-size: 14px;
		}

		.horario-info p {
			font-size: 10px;
		}

		.horario-check {
			width: 16px;
			height: 16px;
			font-size: 8px;
			top: 5px;
			right: 5px;
		}

		/* Legacy support */
		.horario-item {
			padding: 10px 6px !important;
			font-size: 13px !important;
			min-height: 40px;
		}

		.summary-box {
			padding: 15px !important;
		}

		.summary-item {
			font-size: 14px;
			margin-bottom: 10px;
		}

		.summary-label {
			font-size: 13px;
		}
	}

	@media (max-width: 480px) {
		.horarios-grid {
			grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
			gap: 5px;
		}

		.horario-card {
			padding: 10px 6px;
			min-height: 45px;
		}

		.horario-info h5 {
			font-size: 12px;
		}

		.horario-info p {
			font-size: 9px;
		}

		.horario-check {
			width: 14px;
			height: 14px;
			font-size: 7px;
			top: 4px;
			right: 4px;
		}

		/* Legacy support */
		.horario-item {
			padding: 8px 4px !important;
			font-size: 12px !important;
			min-height: 35px;
		}

		.summary-item {
			font-size: 13px;
		}
	}

	.btn-new {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(45deg, var(--dourado), var(--dourado-claro));
		color: var(--dourado-escuro);
		text-decoration: none;
		border-radius: 25px !important;
		font-weight: 600;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
		border: none;
		cursor: pointer;
		font-size: 16px;
		min-height: 48px;
		text-align: center;
		box-sizing: border-box;
	}

	.btn-new::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(45deg, #0d0d0d, #8a6d1f);
		transition: left 0.3s ease;
		z-index: -1;
	}

	.btn-new:hover {
		color: white !important;
		text-decoration: none;
		transform: translateY(-2px);
	}

	.btn-new:hover::before {
		left: 0;
	}



	.button-row {
		align-items: center;
	}

	/* Summary improvements */
	.summary-box {
		border-left: 4px solid #28a745;
		box-shadow: 0 4px 15px rgba(0,0,0,0.1);
	}

	.summary-item {
		margin-bottom: 10px;
		padding: 8px 0;
		border-bottom: 1px solid rgba(255,255,255,0.1);
		font-size: 15px;
	}

	.summary-item:last-child {
		border-bottom: none;
		margin-bottom: 0;
	}

	.summary-label {
		font-weight: bold;
		margin-right: 10px;
		color: #ffffff;
	}

	/* Services Grid Styles */
	.services-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 15px;
		margin: 20px 0;
	}
	
	.service-card {
		background: rgba(255,255,255,0.1);
		border: 2px solid rgba(255,255,255,0.3);
		border-radius: 15px;
		padding: 20px;
		text-align: center;
		cursor: pointer;
		transition: all 0.3s ease;
		position: relative;
		backdrop-filter: blur(10px);
	}
	
	.service-card:hover {
		transform: translateY(-5px);
		border-color: var(--dourado);
		box-shadow: 0 10px 25px rgba(212, 175, 55, 0.32);
		background: rgba(255,255,255,0.2);
	}
	
	.service-card.selected {
		border-color: #28a745;
		background: rgba(40, 167, 69, 0.2);
		box-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
	}
	
	.service-photo {
		width: 80px;
		height: 80px;
		margin: 0 auto 15px;
		border-radius: 50%;
		overflow: hidden;
		border: 3px solid rgba(255,255,255,0.3);
		transition: all 0.3s ease;
	}
	
	.service-card:hover .service-photo {
		border-color: var(--dourado);
	}
	
	.service-card.selected .service-photo {
		border-color: #28a745;
	}
	
	.service-photo img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	
	.service-info h5 {
		color: white;
		margin: 0 0 5px 0;
		font-size: 16px;
		font-weight: 600;
	}
	
	.service-info p {
		color: rgba(255,255,255,0.8);
		margin: 0;
		font-size: 14px;
	}
	
	.service-check {
		position: absolute;
		top: 10px;
		right: 10px;
		width: 25px;
		height: 25px;
		background: #28a745;
		border-radius: 50%;
		display: none;
		align-items: center;
		justify-content: center;
		color: white;
		font-size: 12px;
	}
	
	.service-card.selected .service-check {
		display: flex;
	}
	
	.service-check i {
		color: white;
		font-size: 12px;
	}
	
	/* Loyalty Card Responsive Styles */
	@media (max-width: 768px) {
		.services-grid {
			grid-template-columns: 1fr;
			gap: 15px;
		}
		
		.service-card {
			padding: 15px;
			min-height: 120px;
			gap: 12px;
		}
		
		.service-photo img {
			width: 60px;
			height: 60px;
		}
		
		.service-info h5 {
			font-size: 14px;
		}
		
		.service-info p {
			font-size: 16px;
		}
		
		.loyalty-card-container {
			margin: 15px 0 !important;
			padding: 20px !important;
		}
		
		.loyalty-cards-grid {
			gap: 6px !important;
		}
		
		.loyalty-card {
			width: 40px !important;
			height: 40px !important;
		}
		
		.loyalty-card span {
			font-size: 16px !important;
		}
	}
	
	@media (max-width: 480px) {
		.services-grid {
			grid-template-columns: 1fr;
			gap: 12px;
		}
		
		.service-card {
			padding: 12px;
			min-height: 100px;
			gap: 10px;
		}
		
		.service-photo img {
			width: 50px;
			height: 50px;
		}
		
		.service-info h5 {
			font-size: 13px;
		}
		
		.service-info p {
			font-size: 14px;
		}
		
		.loyalty-card-container {
			padding: 15px !important;
		}
		
		.loyalty-card {
			width: 35px !important;
			height: 35px !important;
		}
		
		.loyalty-card span {
			font-size: 14px !important;
		}
	}
</style>  



<script type="text/javascript">
	$(document).ready(function() {
		$('.sel2').select2({
			
		});
		
		// Initialize steps
		currentStep = 1;
		updateStepDisplay();
		
		// Add click events to steps
		for (let i = 1; i <= 5; i++) {
			document.getElementById(`step-${i}`).addEventListener('click', function() {
				goToStep(i);
			});
		}
	});

	// Step Navigation Variables
	let currentStep = 1;
	const totalSteps = 5;

	// Step Navigation Functions
	function nextStep(step) {
		if (validateStep(step)) {
			if (step < totalSteps) {
				currentStep = step + 1;
				updateStepDisplay();
				if (currentStep === 5) {
					updateSummary();
				}
			}
		}
	}

	function prevStep(step) {
		if (step > 1) {
			currentStep = step - 1;
			updateStepDisplay();
		}
	}

	function updateStepDisplay() {
		// Hide all step contents
		for (let i = 1; i <= totalSteps; i++) {
			document.getElementById(`step-content-${i}`).style.display = 'none';
			document.getElementById(`step-${i}`).classList.remove('active');
		}

		// Show current step content
		document.getElementById(`step-content-${currentStep}`).style.display = 'block';
		document.getElementById(`step-${currentStep}`).classList.add('active');

		// Mark completed steps
		for (let i = 1; i < currentStep; i++) {
			document.getElementById(`step-${i}`).classList.add('completed');
			document.querySelectorAll('.step-line')[i-1]?.classList.add('completed');
		}
	}

	function validateStep(step) {
		switch(step) {
			case 1:
				const telefone = document.getElementById('telefone').value;
				const nome = document.getElementById('nome').value;
				if (!telefone || !nome) {
					alert('Por favor, preencha todos os campos obrigatórios.');
					return false;
				}
				return true;

			case 2:
				const data = document.getElementById('data').value;
				const funcionario = document.getElementById('funcionario').value;
				if (!data || !funcionario) {
					alert('Por favor, selecione a data e o profissional.');
					return false;
				}
				return true;

			case 3:
			const horario = document.getElementById('hora').value;
			if (!horario) {
				alert('Por favor, selecione um horário.');
				return false;
			}
			return true;

			case 4:
				const servico = document.getElementById('servico').value;
				if (!servico) {
					alert('Por favor, selecione um serviço.');
					return false;
				}
				return true;

			default:
				return true;
		}
	}

	function updateSummary() {
		const nome = document.getElementById('nome').value;
		const telefone = document.getElementById('telefone').value;
		const data = document.getElementById('data').value;
		
		// Obter nome do profissional selecionado
		const funcionarioId = document.getElementById('funcionario').value;
		const funcionarioCard = document.querySelector('.professional-card.selected');
		const funcionarioNome = funcionarioCard ? funcionarioCard.querySelector('h5').textContent : '';
		
		// Obter os serviços selecionados (pode ter mais de um)
		const servicosCards = Array.prototype.slice.call(document.querySelectorAll('.service-card.selected'));
		const servicosNomes = servicosCards.map(function(c){ return c.querySelector('h5').textContent; });
		const servicosTotal = servicosCards.reduce(function(soma, c){ return soma + parseFloat(c.getAttribute('data-valor') || 0); }, 0);
		const servicoNome = servicosNomes.join(' + ');
		const servicoTotalF = servicosCards.length ? 'R$ ' + servicosTotal.toFixed(2).replace('.', ',') : '';
		
		// Obter horário selecionado
		const horarioId = document.getElementById('hora').value;
		const horarioCard = document.querySelector('.horario-card.selected');
		const horario = horarioCard ? horarioCard.querySelector('h5').textContent : '';

		// Format date
		const dataFormatada = data ? new Date(data + 'T00:00:00').toLocaleDateString('pt-BR') : '';

		const summaryHTML = `
			<div class="summary-item">
				<span class="summary-label">Nome:</span> ${nome}
			</div>
			<div class="summary-item">
				<span class="summary-label">Telefone:</span> ${telefone}
			</div>
			<div class="summary-item">
				<span class="summary-label">Data:</span> ${dataFormatada}
			</div>
			<div class="summary-item">
				<span class="summary-label">Profissional:</span> ${funcionarioNome}
			</div>
			<div class="summary-item">
				<span class="summary-label">Horário:</span> ${horario}
			</div>
			<div class="summary-item">
				<span class="summary-label">${servicosCards.length > 1 ? 'Serviços:' : 'Serviço:'}</span> ${servicoNome}
			</div>
			<div class="summary-item">
				<span class="summary-label">Total:</span> ${servicoTotalF}
			</div>
		`;

		document.getElementById('summary-content').innerHTML = summaryHTML;
	}

	function goToStep(targetStep) {
		// Only allow navigation to completed steps or the next step
		if (targetStep <= currentStep || targetStep === currentStep + 1) {
			// Validate current step before moving forward
			if (targetStep > currentStep && !validateStep(currentStep)) {
				return;
			}
			
			currentStep = targetStep;
			updateStepDisplay();
			
			if (currentStep === 5) {
				updateSummary();
			}
		}
	}
</script>


<script type="text/javascript">
	
	// ===== Calendario de agendamento =====
	var DIAS_SEMANA_NOMES = ['Domingo','Segunda-Feira','Terça-Feira','Quarta-Feira','Quinta-Feira','Sexta-Feira','Sábado'];
	var calHoje = new Date(); calHoje.setHours(0,0,0,0);
	var calAno = calHoje.getFullYear();
	var calMes = calHoje.getMonth();
	var calSelecionado = document.getElementById('data') ? document.getElementById('data').value : '';

	function dataParaISO(d){
		return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
	}

	function profissionalTrabalhaNoDia(funcionario, iso){
		if(!DIAS_PROFISSIONAIS[funcionario]){ return true; }
		var p = iso.split('-');
		var d = new Date(parseInt(p[0]), parseInt(p[1])-1, parseInt(p[2]));
		return DIAS_PROFISSIONAIS[funcionario].indexOf(DIAS_SEMANA_NOMES[d.getDay()]) !== -1;
	}

	function mudarMesCalendario(delta){
		var alvo = new Date(calAno, calMes + delta, 1);
		var primeiroMesAtual = new Date(calHoje.getFullYear(), calHoje.getMonth(), 1);
		if(alvo < primeiroMesAtual){ return; }
		calAno = alvo.getFullYear();
		calMes = alvo.getMonth();
		renderCalendario();
	}

	function atualizarLegendaCalendario(){
		var legenda = document.getElementById('cal-legenda');
		if(!legenda){ return; }
		if(!calSelecionado){
			legenda.innerHTML = 'Selecione um dia disponível';
			return;
		}
		var partes = calSelecionado.split('-');
		var d = new Date(parseInt(partes[0]), parseInt(partes[1])-1, parseInt(partes[2]));
		legenda.innerHTML = 'Dia selecionado: <b>' + d.toLocaleDateString('pt-BR') + '</b> (' + DIAS_SEMANA_NOMES[d.getDay()] + ')';
	}

	function renderCalendario(){
		var container = document.getElementById('cal-dias');
		if(!container){ return; }

		document.getElementById('cal-titulo').textContent = new Date(calAno, calMes, 1).toLocaleDateString('pt-BR', {month:'long', year:'numeric'});
		document.getElementById('cal-prev').disabled = (calAno === calHoje.getFullYear() && calMes === calHoje.getMonth());

		var primeiroDia = new Date(calAno, calMes, 1);
		var diasNoMes = new Date(calAno, calMes + 1, 0).getDate();
		var funcionario = document.getElementById('funcionario') ? document.getElementById('funcionario').value : '';
		var diasDoProfissional = (funcionario && DIAS_PROFISSIONAIS[funcionario]) ? DIAS_PROFISSIONAIS[funcionario] : null;

		container.innerHTML = '';

		for(var v = 0; v < primeiroDia.getDay(); v++){
			var vazio = document.createElement('button');
			vazio.type = 'button';
			vazio.className = 'calendario-dia vazio';
			vazio.disabled = true;
			container.appendChild(vazio);
		}

		for(var dia = 1; dia <= diasNoMes; dia++){
			var dataObj = new Date(calAno, calMes, dia);
			var iso = dataParaISO(dataObj);
			var botao = document.createElement('button');
			botao.type = 'button';
			botao.className = 'calendario-dia';
			botao.textContent = dia;
			botao.setAttribute('data-data', iso);

			var ehPassado = dataObj < calHoje;
			var naoTrabalha = diasDoProfissional ? diasDoProfissional.indexOf(DIAS_SEMANA_NOMES[dataObj.getDay()]) === -1 : false;

			// Horarios ja reservados no dia (aguardando confirmacao ou confirmados)
			var ocupadosDia = (funcionario && AGENDA_OCUPADA[funcionario] && AGENDA_OCUPADA[funcionario][iso]) ? AGENDA_OCUPADA[funcionario][iso] : [];
			var horariosDoDia = (funcionario && HORARIOS_PROFISSIONAIS[funcionario]) ? HORARIOS_PROFISSIONAIS[funcionario] : [];
			var horasOcupadas = ocupadosDia.map(function(o){ return o.hora; });
			var horasLivres = horariosDoDia.filter(function(h){ return horasOcupadas.indexOf(h) === -1; });
			if(iso === dataParaISO(calHoje)){
				var agora = new Date();
				horasLivres = horasLivres.filter(function(h){ return new Date(iso + 'T' + h + ':00') > agora; });
			}
			var lotado = (!ehPassado && !naoTrabalha && horariosDoDia.length > 0 && horasLivres.length === 0);

			if(ehPassado || naoTrabalha || lotado){ botao.disabled = true; }
			if(iso === dataParaISO(calHoje)){ botao.classList.add('hoje'); }
			if(iso === calSelecionado){ botao.classList.add('selecionado'); }
			// Cores diferentes para dia encerrado, dia sem atendimento e dia lotado
			if(ehPassado){
				botao.classList.add('passado');
				botao.title = 'Dia já passou';
			} else if(naoTrabalha){
				botao.classList.add('fechado');
				botao.title = 'Sem atendimento neste dia';
			} else if(lotado){
				botao.classList.add('lotado');
				botao.title = 'Sem horarios livres neste dia';
			}

			if(ocupadosDia.length > 0){
				botao.classList.add('com-agendamentos');
				var temAguardando = ocupadosDia.some(function(o){ return o.status === 'Aguardando'; });
				var ponto = document.createElement('span');
				ponto.className = 'cal-dot' + (temAguardando ? '' : ' confirmado');
				botao.appendChild(ponto);
				if(!lotado){
					botao.title = horasLivres.length + ' horario(s) livre(s), ' + ocupadosDia.length + ' ocupado(s)';
				}
			}

			botao.onclick = function(){ selecionarDiaCalendario(this.getAttribute('data-data')); };
			container.appendChild(botao);
		}

		atualizarLegendaCalendario();
	}

	function selecionarDiaCalendario(iso){
		calSelecionado = iso;
		document.getElementById('data').value = iso;
		renderCalendario();
		mudarFuncionario();
	}

	// ===== Envio do agendamento pelo WhatsApp =====
	var urlWhatsAppAtual = '';

	function montarUrlWhatsApp(funcionario, servico, data, hora, cliente, observacao){
		var texto = '*Novo Agendamento*\n'
			+ 'Funcionário: ' + funcionario + '\n'
			+ 'Serviço: ' + servico + '\n'
			+ 'Data: ' + data + '\n'
			+ 'Hora: ' + hora + '\n'
			+ 'Cliente: ' + cliente
			+ (observacao ? '\nObs: ' + observacao : '')
			+ '\nStatus: Aguardando confirmação';

		urlWhatsAppAtual = 'https://wa.me/<?php echo $tel_whatsapp ?>?text=' + encodeURIComponent(texto);
		return urlWhatsAppAtual;
	}

	function mostrarBotaoWhatsApp(){
		var caixa = document.getElementById('mensagem');
		if(!caixa || !urlWhatsAppAtual){ return; }

		var anterior = document.getElementById('btn-whatsapp-agendamento');
		if(anterior){ anterior.remove(); }

		var dica = document.createElement('p');
		dica.id = 'dica-whatsapp-agendamento';
		dica.style.cssText = 'color:#fff; font-size:13px; margin:10px 0 0 0;';
		dica.textContent = 'Confirme o envio da mensagem no WhatsApp para o estabelecimento receber seu agendamento.';

		var botao = document.createElement('a');
		botao.id = 'btn-whatsapp-agendamento';
		botao.href = urlWhatsAppAtual;
		botao.target = '_blank';
		botao.rel = 'noopener';
		botao.className = 'btn-whatsapp';
		botao.innerHTML = '<i class="fa fa-whatsapp" aria-hidden="true"></i> Enviar no WhatsApp';

		caixa.appendChild(dica);
		caixa.appendChild(botao);
	}

	// Desenha o calendario assim que a pagina carrega
	if(document.getElementById('cal-dias')){
		renderCalendario();
	}

	function mudarFuncionario(){
		var funcionario = $('#funcionario').val();
		var data = $('#data').val();
		console.log(data)
		var hora = $('#hora_rec').val();
		listarHorarios(funcionario, data, hora);
		listarFuncionario();	

	}

	function selecionarProfissional(id, nome) {
		// Remove seleção anterior
		$('.professional-card').removeClass('selected');
		
		// Adiciona seleção ao card clicado
		$('[data-id="' + id + '"]').addClass('selected');
		
		// Define o valor no campo hidden
		$('#funcionario').val(id);

		// Revalida o dia escolhido para o profissional selecionado
		if(calSelecionado && !profissionalTrabalhaNoDia(id, calSelecionado)){
			calSelecionado = '';
			document.getElementById('data').value = '';
		}
		renderCalendario();
		
		// Chama a função original para atualizar horários
		mudarFuncionario();
	}
	
	// O cliente pode marcar mais de um serviço; o campo #servico guarda os ids separados por virgula
	function alternarServico(card) {
		card.classList.toggle('selected');
		atualizarServicosSelecionados();
	}

	function servicosSelecionados() {
		return Array.prototype.slice.call(document.querySelectorAll('.service-card.selected'));
	}

	function atualizarServicosSelecionados() {
		var cards = servicosSelecionados();
		var ids = cards.map(function(c){ return c.getAttribute('data-id'); });
		var nomes = cards.map(function(c){ return c.getAttribute('data-nome'); });
		var total = cards.reduce(function(soma, c){ return soma + parseFloat(c.getAttribute('data-valor') || 0); }, 0);

		$('#servico').val(ids.join(','));
		$('#nome_serv').val(nomes.join(' + '));

		var totalEl = document.getElementById('total-servicos');
		if(totalEl){
			if(cards.length === 0){
				totalEl.innerHTML = '';
			}else{
				totalEl.innerHTML = 'Total: R$ ' + total.toFixed(2).replace('.', ',') + ' (' + cards.length + (cards.length > 1 ? ' serviços' : ' serviço') + ')';
			}
		}
	}
</script>



<script type="text/javascript">
	function listarHorarios(funcionario, data, hora){	

		
		$.ajax({
			url: "ajax/listar-horarios.php",
			method: 'POST',
			data: {funcionario, data, hora},
			dataType: "text",

			success:function(result){
				$("#listar-horarios").html(result);
			}
		});
	}
</script>







<script type="text/javascript">
	
	function salvar(){
		$('#id').val('');
			}
</script>




<script>

	$("#form-agenda").submit(function (event) {
		event.preventDefault();


		var formData = new FormData(this);

		$.ajax({
			url: "ajax/agendar.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem').text('');
				$('#mensagem').removeClass()
				if (mensagem.trim() == "Agendado com Sucesso") {
				// Criar confetti
				criarConfetti();
				
				// Aplicar estilo de sucesso
				$('#mensagem').removeClass().addClass('mensagem-container');
				$('#mensagem').html('<div class="mensagem-sucesso"><span class="icone-sucesso">🎉</span><span class="texto-sucesso">✅ ' + mensagem + '</span></div>');
				$('#mensagem').append('<div class="mensagem-sucesso" style="margin-top:6px"><span class="texto-sucesso">⏳ Seu horário ficou <b>aguardando confirmação</b> do profissional.</span></div>');
				
				// A mensagem de sucesso permanece visivel, junto do botao do WhatsApp
				$('#mensagem').stop(true, true).fadeIn(0);

					var nome = $('#nome').val();
					var data = $('#data').val();
					var campoHora = document.getElementById('hora');
					var hora = campoHora ? campoHora.value : '';
					if(!hora){
						var cardHora = document.querySelector('.horario-card.selected');
						hora = cardHora ? (cardHora.getAttribute('data-hora') || '') : '';
					}
					var obs = $('#obs').val();
					var nome_func = $('#nome_func').val();
					var nome_serv = $('#nome_serv').val();

					var dataF = data.split("-");
					var dia = dataF[2];
					var mes = dataF[1];
					var ano = dataF[0];
					var dataFormatada = dia + '/' + mes + '/' + ano;

					var horaF = hora.split(':');
					var horaH = horaF[0];
					var horaM = horaF[1];
					var horaFormatada = horaH + ':' + horaM;

					var msg_agendamento = "<?=$msg_agendamento?>";

					if(msg_agendamento == 'Sim'){

				let a= document.createElement('a');
			          a.target= '_blank';
			          a.href= montarUrlWhatsApp(nome_func, nome_serv, dataFormatada, horaFormatada, nome, obs);
			          mostrarBotaoWhatsApp();
			          window.open(urlWhatsAppAtual, '_blank');
			          return;		

			      }


				}


				else if (mensagem.trim() == "Editado com Sucesso") {
				// Criar confetti
				criarConfetti();
				
				// Aplicar estilo de sucesso
				$('#mensagem').removeClass().addClass('mensagem-container');
				$('#mensagem').html('<div class="mensagem-sucesso"><span class="icone-sucesso">✏️</span><span class="texto-sucesso">✅ ' + mensagem + '</span></div>');
				$('#mensagem').append('<div class="mensagem-sucesso" style="margin-top:6px"><span class="texto-sucesso">⏳ Alterações enviadas: o horário volta para <b>aguardando confirmação</b>.</span></div>');
				
				// A mensagem de sucesso permanece visivel, junto do botao do WhatsApp
				$('#mensagem').stop(true, true).fadeIn(0);

					var nome = $('#nome').val();
					var data = $('#data').val();
					var campoHora = document.getElementById('hora');
					var hora = campoHora ? campoHora.value : '';
					if(!hora){
						var cardHora = document.querySelector('.horario-card.selected');
						hora = cardHora ? (cardHora.getAttribute('data-hora') || '') : '';
					}
					var obs = $('#obs').val();
					var nome_func = $('#nome_func').val();
					var nome_serv = $('#nome_serv').val();

					var dataF = data.split("-");
					var dia = dataF[2];
					var mes = dataF[1];
					var ano = dataF[0];
					var dataFormatada = dia + '/' + mes + '/' + ano;

					var horaF = hora.split(':');
					var horaH = horaF[0];
					var horaM = horaF[1];
					var horaFormatada = horaH + ':' + horaM;

					var msg_agendamento = "<?=$msg_agendamento?>";

					if(msg_agendamento == 'Sim'){

				let a= document.createElement('a');
			          a.target= '_blank';
			          a.href= montarUrlWhatsApp(nome_func, nome_serv, dataFormatada, horaFormatada, nome, obs);
			          mostrarBotaoWhatsApp();
			          window.open(urlWhatsAppAtual, '_blank');
			          return;		

			      }


				}


				 else {
					// Aplicar estilo de erro
					$('#mensagem').removeClass().addClass('mensagem-container');
					$('#mensagem').html('<div class="mensagem-erro"><span class="icone-erro">⚠️</span><span class="texto-erro">' + mensagem + '</span></div>');
					
					// Auto-hide após 7 segundos
					setTimeout(function() {
						$('#mensagem').fadeOut(500);
					}, 7000);
				}

			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});

</script>













<script type="text/javascript">
	function listarFuncionario(){	
		var func = $("#funcionario").val();
		
		$.ajax({
			url: "ajax/listar-funcionario.php",
			method: 'POST',
			data: {func},
			dataType: "text",

			success:function(result){
				$("#nome_func").val(result);
			}
		});
	}
</script>


<script type="text/javascript">
	function mudarServico(){	
		var serv = $("#servico").val();
		
		$.ajax({
			url: "ajax/listar-servico.php",
			method: 'POST',
			data: {serv},
			dataType: "text",

			success:function(result){
				$("#nome_serv").val(result);
			}
		});
	}
</script>
