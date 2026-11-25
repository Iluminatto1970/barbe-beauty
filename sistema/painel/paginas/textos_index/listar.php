<?php 
require_once("../../../conexao.php");
$tabela = 'textos_index';

// Lista apenas os textos do segmento configurado (barbearia ou salao)
$segmento_texto = (isset($segmento) && $segmento == 'salao') ? 'salao' : 'barbearia';
$query = $pdo->prepare("SELECT * FROM $tabela WHERE segmento = :v1 ORDER BY id desc");
$query->execute(array(':v1' => $segmento_texto));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){

echo <<<HTML
	<small>
	<table class="table table-hover" id="tabela">
	<thead> 
	<tr> 
	<th>Título</th>	
	<th>Descrição</th>		
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
HTML;

for($i=0; $i < $total_reg; $i++){
	foreach ($res[$i] as $key => $value){}
	$id = $res[$i]['id'];
	$titulo = $res[$i]['titulo'];
	$descricao = $res[$i]['descricao'];

	$descricaoF = mb_strimwidth($descricao, 0, 100, "...");
	
		
	
$__bb_esc1 = bb_js($descricao);
echo <<<HTML
<tr class="">
<td>{$titulo}</td>
<td>{$descricaoF}</td>
<td>
		<big><a href="#" onclick="editar('{$id}','{$titulo}',{$__bb_esc1})" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

		
		<li class="dropdown head-dpdn2" style="display: inline-block;">
		<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-trash-o text-danger"></i></big></a>

		<ul class="dropdown-menu" style="margin-left:-230px;">
		<li>
		<div class="notification_desc2">
		<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
		</div>
		</li>										
		</ul>
		</li>


		</td>
</tr>
HTML;

}

echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>
</small>
HTML;


}else{
	echo '<small>Não possui nenhum registro Cadastrado!</small>';
}

?>


<script type="text/javascript">
	$(document).ready( function () {
    $('#tabela').DataTable({
    		"ordering": false,
			"stateSave": true
    	});
    $('#tabela_filter label input').focus();
} );
</script>


<script type="text/javascript">
	function editar(id, titulo, descricao){
		$('#id').val(id);
		$('#titulo').val(titulo);
		$('#descricao').val(descricao);
		$('#titulo_inserir').text('Editar Registro');
		$('#modalForm').modal('show');
	}

	function limparCampos(){
		$('#titulo').val('');
		$('#descricao').val('');
		$('#id').val('');
	}
</script>
