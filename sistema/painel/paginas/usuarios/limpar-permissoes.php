<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();

$id_usuario = $_POST['id'];

$pdo->prepare("DELETE FROM usuarios_permissoes where usuario = :v1")->execute(array(':v1' => $id_usuario));

?>
