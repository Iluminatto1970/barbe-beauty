<?php 
require_once("../../../conexao.php");
$tabela = 'agendamentos';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }
bb_csrf_check_request();

$id = $_POST['id'];

$stmt = $pdo->prepare("DELETE FROM $tabela where id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();

echo 'Excluído com Sucesso';


?>