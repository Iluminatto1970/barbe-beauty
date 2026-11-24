<?php 
require_once("../../../conexao.php");
$tabela = 'servicos';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];

$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->bindValue(':id', $id);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
$foto = $res[0]['foto'];

if($foto != "sem-foto.jpg"){
	@unlink('../../img/servicos/'.$foto);
}

$stmt = $pdo->prepare("DELETE from $tabela where id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
echo 'Excluído com Sucesso';
 ?>