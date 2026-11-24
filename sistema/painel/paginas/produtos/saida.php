<?php
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'saidas';
@session_start();
$id_usuario = $_SESSION['id'];

$id_produto = $_POST['id'];
$estoque = $_POST['estoque'];
$quantidade_saida = $_POST['quantidade_saida'];
$motivo_saida = $_POST['motivo_saida'];

$novo_estoque = $estoque - $quantidade_saida;

$query = $pdo->prepare("INSERT INTO $tabela SET produto = :id_produto, quantidade = :quantidade, motivo = :motivo, usuario = :usuario, data = curDate()");
$query->execute(array(':id_produto' => $id_produto, ':quantidade' => $quantidade_saida, ':motivo' => $motivo_saida, ':usuario' => $id_usuario));

//atualizar o total no estoque do produto
$up = $pdo->prepare("UPDATE produtos SET estoque = :novo WHERE id = :id_produto");
$up->execute(array(':novo' => $novo_estoque, ':id_produto' => $id_produto));

echo 'Salvo com Sucesso';
?>
