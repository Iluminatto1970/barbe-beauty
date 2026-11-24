<?php 
require_once("../sistema/conexao.php");

$funcionario = $_POST['func'];

$query = $pdo->prepare("SELECT * FROM usuarios where id = :v1 ");
$query->execute(array(':v1' => $funcionario));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$nome = $res[0]['nome'];
		
}

echo $nome;

?>


