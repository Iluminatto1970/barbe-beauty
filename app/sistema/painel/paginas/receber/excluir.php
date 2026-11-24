<?php 
require_once("../../../conexao.php");
$tabela = 'receber';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];

$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->bindValue(':id', $id);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
$foto = $res[0]['foto'];
$produto = $res[0]['produto'];
$quantidade = $res[0]['quantidade'];

if($foto != "sem-foto.jpg"){
	@unlink('../../img/contas/'.$foto);
}

if($produto > 0){
	$query = $pdo->prepare("SELECT * FROM produtos where id = :produto");
$query->bindValue(':produto', $produto);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
$estoque = $res[0]['estoque'];

$total_estoque = $estoque + $quantidade;
$stmt = $pdo->prepare("UPDATE produtos SET estoque = :total_estoque, valor_compra = '0' WHERE id = :produto");
$stmt->bindValue(':produto', $produto);
$stmt->bindValue(':total_estoque', $total_estoque);
$stmt->execute();
}

$stmt = $pdo->prepare("DELETE from $tabela where id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
echo 'Excluído com Sucesso';
 ?>