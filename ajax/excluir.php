<?php
require_once("../sistema/conexao.php");

// Demonstracao: nao apaga nada no banco ficticio.
if (bb_site_demo()) {
	echo 'Cancelado com Sucesso';
	exit();
}

$id = @$_POST['id'];

$query = $pdo->prepare("DELETE FROM agendamentos where id = :id");
$query->execute(array(':id' => $id));

echo 'Cancelado com Sucesso';
?>
