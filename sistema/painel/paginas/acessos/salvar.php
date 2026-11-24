<?php 
require_once("../../../conexao.php");
$tabela = 'acessos';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];
$nome = $_POST['nome'];
$chave = $_POST['chave'];
$grupo = $_POST['grupo'];

//validar nome
$query = $pdo->prepare("SELECT * from $tabela where nome = :nome and grupo = :grupo");
$query->bindValue(':grupo', $grupo);
$query->bindValue(':nome', $nome);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 and $id != $res[0]['id']){
	echo 'Nome já Cadastrado, escolha outro!!';
	exit();
}


//validar nome
$query = $pdo->prepare("SELECT * from $tabela where chave = :chave and grupo = :grupo");
$query->bindValue(':chave', $chave);
$query->bindValue(':grupo', $grupo);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 and $id != $res[0]['id']){
	echo 'Chave já Cadastrada, escolha outra!!';
	exit();
}

if($id == ""){
	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, grupo = :grupo, chave = :chave");
$query->bindValue(':grupo', $grupo);
}else{
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, grupo = :grupo, chave = :chave WHERE id = :id");
$query->bindValue(':grupo', $grupo);
$query->bindValue(':id', $id);
}

$query->bindValue(":nome", "$nome");
$query->bindValue(":chave", "$chave");
$query->execute();

echo 'Salvo com Sucesso';
 ?>