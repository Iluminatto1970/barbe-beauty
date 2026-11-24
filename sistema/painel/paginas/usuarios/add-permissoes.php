<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();

$id_usuario = $_POST['id'];

$pdo->prepare("DELETE FROM usuarios_permissoes where usuario = :v1")->execute(array(':v1' => $id_usuario));

$query = $pdo->query("SELECT * FROM acessos order by id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	for($i=0; $i < $total_reg; $i++){
		foreach ($res[$i] as $key => $value){}
			$nome = $res[$i]['nome'];
		$chave = $res[$i]['chave'];
		$id = $res[$i]['id'];

		$query = $pdo->prepare("INSERT INTO usuarios_permissoes SET permissao = :v1, usuario = :v2");
		$query->execute(array(':v1' => $id, ':v2' => $id_usuario));

	}
}

?>
