<?php 
require_once("../../../conexao.php");
$tabela = 'grupo_acessos';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];

$query2 = $pdo->prepare("SELECT * FROM acessos where grupo = :id");
$query2->bindValue(':id', $id);
$query2->execute();
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
$total_reg2 = @count($res2);
if($total_reg2 > 0){
	echo 'Não é possível excluir o registro, pois existem acessos relacionados a ele primeiro exclua os acessos e depois exclua esse grupo!';
	exit();
}

$stmt = $pdo->prepare("DELETE from $tabela where id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
echo 'Excluído com Sucesso';
?>