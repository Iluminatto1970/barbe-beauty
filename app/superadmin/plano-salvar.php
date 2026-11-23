<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);

$id    = (int) @$_POST['id'];
$nome  = trim((string) @$_POST['nome']);
$valor = (float) str_replace(',', '.', (string) @$_POST['valor']);
$prof  = max(0, (int) @$_POST['limite_profissionais']);
$agd   = max(0, (int) @$_POST['limite_agendamentos']);
$ativo = (@$_POST['ativo'] == 'Não') ? 'Não' : 'Sim';

if ($nome === '') {
    header('Location: planos.php');
    exit;
}

if ($id > 0) {
    $q = $pdo->prepare("UPDATE saas_planos SET nome = :nome, valor = :valor, limite_profissionais = :prof,
                        limite_agendamentos = :agd, ativo = :ativo WHERE id = :id");
    $q->bindValue(':id', $id);
} else {
    $q = $pdo->prepare("INSERT INTO saas_planos (nome, valor, limite_profissionais, limite_agendamentos, ativo)
                        VALUES (:nome, :valor, :prof, :agd, :ativo)");
}

$q->bindValue(':nome', $nome);
$q->bindValue(':valor', $valor);
$q->bindValue(':prof', $prof, PDO::PARAM_INT);
$q->bindValue(':agd', $agd, PDO::PARAM_INT);
$q->bindValue(':ativo', $ativo);
$q->execute();

header('Location: planos.php');
exit;
