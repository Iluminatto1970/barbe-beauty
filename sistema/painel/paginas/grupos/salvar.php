<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'grupo_acessos';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];
$nome = $_POST['nome'];


//validar nome
$query = $pdo->prepare("SELECT * from $tabela where nome = :nome");
$query->bindValue(':nome', $nome);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 and $id != $res[0]['id']){
	echo 'Nome já Cadastrado, escolha outro!!';
	exit();
}


if($id == ""){
	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome");
}else{
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome WHERE id = :id");
$query->bindValue(':id', $id);
}

$query->bindValue(":nome", "$nome");
$query->execute();

echo 'Salvo com Sucesso';
 ?>