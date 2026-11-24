<?php 
require_once("../../../conexao.php");
@session_start();
$usuario = @$_SESSION['id'];

$funcionario = @$_POST['funcionario'];
$data = @$_POST['data'];

if($data == ""){
	$data = date('Y-m-d');
}

if($funcionario == ""){
	echo '<small>Selecione um Funcionário!</small>';
	exit();
}

echo <<<HTML
<small>
HTML;
$query = $pdo->prepare("SELECT * FROM agendamentos where funcionario = :v1 and data = :v2 ORDER BY hora asc");
$query->execute(array(':v1' => $funcionario, ':v2' => $data));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
$id = $res[$i]['id'];
$funcionario = $res[$i]['funcionario'];
$cliente = $res[$i]['cliente'];
$hora = $res[$i]['hora'];
$data = $res[$i]['data'];
$usuario = $res[$i]['usuario'];
$data_lanc = $res[$i]['data_lanc'];
$obs = $res[$i]['obs'];
$status = $res[$i]['status'];
$servico = $res[$i]['servico'];

$dataF = implode('/', array_reverse(explode('-', $data)));
$horaF = date("H:i", strtotime($hora));


if($status == 'Concluído'){		
	$classe_linha = '';
}else{		
	$classe_linha = 'text-muted';
}



if($status == 'Aguardando'){
	$imagem = 'icone-relogio.png';
	$classe_status = 'ocultar';
	$classe_confirmar = '';
	$texto_status = 'Aguardando confirmação';
	$cor_status = '#b7791f';
}elseif($status == 'Agendado'){
	$imagem = 'icone-relogio.png';
	$classe_status = '';	
	$classe_confirmar = 'ocultar';
	$texto_status = 'Confirmado';
	$cor_status = '#00a085';
}else{
	$imagem = 'icone-relogio-verde.png';
	$classe_status = 'ocultar';
	$classe_confirmar = 'ocultar';
	$texto_status = $status;
	$cor_status = '#9aa0a6';
}

$query2 = $pdo->prepare("SELECT * FROM usuarios where id = :v1");
$query2->execute(array(':v1' => $usuario));
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_usu = $res2[0]['nome'];
}else{
	$nome_usu = 'Sem Usuário';
}


// O agendamento pode ter mais de um serviço (lista separada por virgula)
$lista_serv = (isset($res[$i]['servicos']) && trim($res[$i]['servicos']) != '') ? $res[$i]['servicos'] : $servico;
$ids_serv = array_values(array_unique(array_filter(array_map('intval', explode(',', $lista_serv)))));
$nome_serv = 'Não Lançado';
$valor_serv = '';
if(@count($ids_serv) > 0){
	$query2 = $pdo->query("SELECT * FROM servicos where id IN (".implode(',', $ids_serv).")");
	$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
	if(@count($res2) > 0){
		$nomes_serv = array();
		$total_serv = 0;
		foreach($ids_serv as $id_serv){
			foreach($res2 as $serv_item){
				if($serv_item['id'] == $id_serv){
					$nomes_serv[] = $serv_item['nome'];
					$total_serv += $serv_item['valor'];
				}
			}
		}
		$nome_serv = implode(' + ', $nomes_serv);
		$valor_serv = number_format($total_serv, 2, '.', '');
	}
}


$query2 = $pdo->prepare("SELECT * FROM clientes where id = :v1");
$query2->execute(array(':v1' => $cliente));
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_cliente = $res2[0]['nome'];
	$total_cartoes = $res2[0]['cartoes'];
}else{
	$nome_cliente = 'Sem Cliente';
	$total_cartoes = 0;
}

if($total_cartoes >= $quantidade_cartoes and $status == 'Agendado'){
	$ocultar_cartoes = '';
}else{
	$ocultar_cartoes = 'ocultar';
}

//retirar aspas do texto do obs
$obs = str_replace('"', "**", $obs);

echo <<<HTML
			<div class="col-xs-12 col-md-4 widget cardTarefas">
        		<div class="r3_counter_box">     		
        		
        		

				<li class="dropdown head-dpdn2" style="list-style-type: none;">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
		<button type="button" class="close" title="Excluir agendamento" style="margin-top: -10px">
					<span aria-hidden="true"><big>&times;</big></span>
				</button>
				</a>

		<ul class="dropdown-menu" style="margin-left:-30px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}', '{$horaF}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>


		<div class="row">
        		<div class="col-md-3">
        			 <img class="icon-rounded-vermelho" src="img/{$imagem}" width="45px" height="45px">
        		</div>
        		<div class="col-md-9">
        			<h5><strong>{$horaF}</strong> <a href="#" onclick="fecharServico('{$id}', '{$cliente}', '{$servico}', '{$valor_serv}', '{$funcionario}', '{$nome_serv}')" title="Finalizar Serviço" class="{$classe_status}"> <img class="icon-rounded-vermelho" src="img/check-square.png" width="15px" height="15px"></a> <a href="#" onclick="confirmarAgendamento('{$id}')" title="Confirmar agendamento" class="{$classe_confirmar}"> <img class="icon-rounded-vermelho" src="img/icone-relogio-verde.png" width="15px" height="15px"></a></h5>

        			
        		</div>
        		</div>
        		
        					
        		<hr style="margin-top:-2px; margin-bottom: 3px">
					<div align="center"><small style="color:{$cor_status}; font-size:10px; text-transform:uppercase; letter-spacing:.5px; font-weight:600">{$texto_status}</small></div>
                    <div class="stats esc" align="center">
                      <span>
                      
                        <small> <span class="{$ocultar_cartoes}" style=""><img class="icon-rounded-vermelho" src="img/presente.jpg" width="20px" height="20px"></span> {$nome_cliente} (<i><span style="color:#061f9c">{$nome_serv}</span></i>)</small></span>
                    </div>
                </div>
        	</div>
HTML;
}

}else{
	echo 'Nenhum horário para essa Data!';
}

?>





<script type="text/javascript">
	function fecharServico(id, cliente, servico, valor_servico, funcionario, nome_serv){
	
		$('#id_agd').val(id);
		$('#cliente_agd').val(cliente);		
		$('#servico_agd').val(servico);	
		$('#valor_serv_agd').val(valor_servico);	
		$('#funcionario_agd').val(funcionario).change();	
		$('#titulo_servico').text(nome_serv);	
		$('#descricao_serv_agd').val(nome_serv);	

		$('#modalServico').modal('show');
	}
</script>
