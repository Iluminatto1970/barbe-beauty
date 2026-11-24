<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'textos_index';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];
$titulo = $_POST['titulo'];
$descricao = $_POST['descricao'];

// Cada texto pertence ao segmento configurado no sistema
$segmento_texto = (isset($segmento) && $segmento == 'salao') ? 'salao' : 'barbearia';

if($id == ""){
	$query = $pdo->prepare("INSERT INTO $tabela SET titulo = :titulo, descricao = :descricao, segmento = :segmento_texto");
$query->bindValue(':segmento_texto', $segmento_texto);
}else{
	$query = $pdo->prepare("UPDATE $tabela SET titulo = :titulo, descricao = :descricao WHERE id = :id");
$query->bindValue(':id', $id);
}

$query->bindValue(":titulo", "$titulo");
$query->bindValue(":descricao", "$descricao");
$query->execute();

echo 'Salvo com Sucesso';
 ?>