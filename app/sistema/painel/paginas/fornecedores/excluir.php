<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'fornecedores';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];
$stmt = $pdo->prepare("DELETE from $tabela where id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
echo 'Excluído com Sucesso';
 ?>