<?php
require_once("../sistema/conexao.php");

// Demonstracao: nao grava nada no banco ficticio.
if (bb_site_demo()) {
	echo 'Cadastro simulado com Sucesso';
	exit();
}

$tabela = 'clientes';

$nome = $_POST['nome'];
$telefone = $_POST['telefone'];

//validar tel
$query = $pdo->prepare("SELECT * from $tabela where telefone = :telefone");
$query->execute(array(':telefone' => $telefone));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	echo 'Telefone já Cadastrado, você já está cadastrado!!';
	exit();
}

$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, telefone = :telefone, data_cad = curDate(), cartoes = '0', alertado = 'Não'");
$query->execute(array(':nome' => $nome, ':telefone' => $telefone));

echo 'Salvo com Sucesso';
?>
