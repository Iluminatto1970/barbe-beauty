<?php
require_once("../../../conexao.php");

$quantidade = $_POST['quant'];
$produto = $_POST['produto'];

$query = $pdo->prepare("SELECT * FROM produtos where id = :produto");
$query->execute(array(':produto' => $produto));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
$valor = $res[0]['valor_venda'];

echo $valor * $quantidade;
?>
