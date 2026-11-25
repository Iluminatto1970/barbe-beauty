<?php
require_once("../sistema/conexao.php");

$bb_csrf = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!bb_csrf_validate($bb_csrf)) {
    bb_resposta_texto(403, 'Sessão expirada. Recarregue a página e tente novamente.');
}

$bb_espera = bb_rate_limit_publico_espera('cadastro', 10, 3600);
if ($bb_espera > 0) {
    bb_resposta_texto(429, 'Muitos cadastros em pouco tempo. Tente novamente mais tarde.');
}

// Demonstracao: nao grava nada no banco ficticio.
if (bb_site_demo()) {
    echo 'Cadastro simulado com Sucesso';
    exit();
}

$tabela = 'clientes';

$nome = trim((string) @$_POST['nome']);
$telefone = trim((string) @$_POST['telefone']);

if ($nome === '' || mb_strlen($nome, 'UTF-8') > 100) {
    echo 'Informe um nome válido.';
    exit();
}

if ($telefone === '' || mb_strlen($telefone, 'UTF-8') > 20) {
    echo 'Informe um telefone válido.';
    exit();
}

//validar tel
$query = $pdo->prepare("SELECT * from $tabela where telefone = :telefone");
$query->execute(array(':telefone' => $telefone));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($res) > 0) {
    echo 'Telefone já Cadastrado, você já está cadastrado!!';
    exit();
}

$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, telefone = :telefone, data_cad = curDate(), cartoes = '0', alertado = 'Não'");
$query->execute(array(':nome' => $nome, ':telefone' => $telefone));

bb_rate_limit_publico_conta('cadastro', 3600);
echo 'Salvo com Sucesso';
?>
