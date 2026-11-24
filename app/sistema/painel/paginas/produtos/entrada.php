<?php
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'entradas';
@session_start();
$id_usuario = $_SESSION['id'];

$id_produto = $_POST['id'];
$estoque = $_POST['estoque'];
$quantidade_entrada = $_POST['quantidade_entrada'];
$motivo_entrada = $_POST['motivo_entrada'];

$novo_estoque = $estoque + $quantidade_entrada;

$query = $pdo->prepare("INSERT INTO $tabela SET produto = :id_produto, quantidade = :quantidade, motivo = :motivo, usuario = :usuario, data = curDate()");
$query->execute(array(':id_produto' => $id_produto, ':quantidade' => $quantidade_entrada, ':motivo' => $motivo_entrada, ':usuario' => $id_usuario));

//atualizar o total no estoque do produto
$up = $pdo->prepare("UPDATE produtos SET estoque = :novo WHERE id = :id_produto");
$up->execute(array(':novo' => $novo_estoque, ':id_produto' => $id_produto));

echo 'Salvo com Sucesso';
?>
