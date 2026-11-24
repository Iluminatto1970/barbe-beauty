<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();

$id_usuario = $_POST['id'];

$pdo->query("DELETE FROM usuarios_permissoes where usuario = '$id_usuario'");

?>