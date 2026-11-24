<?php 
require_once("../sistema/conexao.php");

$servico = $_POST['serv'];

$query = $pdo->prepare("SELECT * FROM servicos where id = :v1 ");
$query->execute(array(':v1' => $servico));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$nome = $res[0]['nome'];
		
}

echo $nome;

?>


