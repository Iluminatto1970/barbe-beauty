<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();

$id_usuario = $_POST['idusuario'];
$id_permissao = $_POST['idpermissao'];

$query = $pdo->prepare("SELECT * FROM usuarios_permissoes where permissao = :v1 and usuario = :v2");
$query->execute(array(':v1' => $id_permissao, ':v2' => $id_usuario));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$pdo->prepare("DELETE FROM usuarios_permissoes where permissao = :v1 and usuario = :v2")->execute(array(':v1' => $id_permissao, ':v2' => $id_usuario));
}else{
	$pdo->prepare("INSERT INTO usuarios_permissoes SET permissao = :v1, usuario = :v2")->execute(array(':v1' => $id_permissao, ':v2' => $id_usuario));
}

?>
