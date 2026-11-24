<?php 
require_once("../../../conexao.php");
$tabela = 'comentarios';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];
$acao = $_POST['acao'];

$stmt = $pdo->prepare("UPDATE $tabela SET ativo = :acao where id = :id");
$stmt->bindValue(':acao', $acao);
$stmt->bindValue(':id', $id);
$stmt->execute();
echo 'Alterado com Sucesso';
 ?>