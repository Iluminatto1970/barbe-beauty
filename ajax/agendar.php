<?php
require_once("../sistema/conexao.php");

// O formulario publico so cria agendamentos. Sem token da propria sessao,
// uma pagina externa nao consegue disparar reservas em nome do visitante.
$bb_csrf = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!bb_csrf_validate($bb_csrf)) {
    bb_resposta_texto(403, 'Sessão expirada. Recarregue a página e tente novamente.');
}

$bb_espera = bb_rate_limit_publico_espera('agendamento', 20, 3600);
if ($bb_espera > 0) {
    bb_resposta_texto(429, 'Muitas solicitações em pouco tempo. Tente novamente mais tarde.');
}

// Modo demonstracao: no site de demonstracao o agendamento simula a
// reserva. Validamos data, horario e servico como no fluxo real, mas no
// final nada e gravado (nem cliente, nem agendamento). A decisao e do
// servidor: nenhum campo do formulario liga ou desliga a demonstracao.
$bb_demo = bb_site_demo();

// Cota do plano gratuito: sem agendamentos quando o limite do mes acabou
// (a demonstracao nao consome cota, porque nao grava nada)
if (!$bb_demo && bb_quota_atingida($pdo)) {
    echo 'As reservas online estão temporariamente indisponíveis. Fale com o estabelecimento pelo WhatsApp.';
    exit();
}

$telefone = trim((string) @$_POST['telefone']);
$nome = trim((string) @$_POST['nome']);
$funcionario = (int) @$_POST['funcionario'];
$hora = trim((string) @$_POST['hora']);
$ids_servicos = array();
foreach (explode(',', (string) @$_POST['servico']) as $cod_servico) {
    $cod_servico = intval(trim($cod_servico));
    if ($cod_servico > 0 and !in_array($cod_servico, $ids_servicos)) {
        $ids_servicos[] = $cod_servico;
    }
}
if (count($ids_servicos) == 0) {
    echo 'Escolha um Serviço para Agendar!';
    exit();
}
$servico = $ids_servicos[0];
$lista_servicos = implode(',', $ids_servicos);
$obs = trim((string) @$_POST['obs']);
$data = trim((string) @$_POST['data']);
$id = trim((string) @$_POST['id']);

// O endpoint publico nao edita agendamento existente. A edicao acontece
// no painel autenticado; aceitar id aqui permitiria alterar a reserva de
// qualquer outra pessoa apenas informando o numero.
if ($id !== '') {
    bb_resposta_texto(403, 'Operação não permitida.');
}

if ($nome === '' || mb_strlen($nome, 'UTF-8') > 100) {
    echo 'Informe um nome válido.';
    exit();
}

if ($telefone === '' || mb_strlen($telefone, 'UTF-8') > 20) {
    echo 'Informe um telefone válido.';
    exit();
}

if ($funcionario <= 0) {
    echo 'Escolha um Profissional para Agendar!';
    exit();
}

if ($obs !== '' && mb_strlen($obs, 'UTF-8') > 100) {
    echo 'A observação deve ter no máximo 100 caracteres.';
    exit();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo 'Escolha uma Data para Agendar!';
    exit();
}

if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
    echo 'Escolha um Horário para Agendar!';
    exit();
}

if ($data < date('Y-m-d')) {
    echo 'Não é possível agendar em uma data que já passou!';
    exit();
}

if ($data == date('Y-m-d') and strtotime($data . ' ' . $hora) <= time()) {
    echo 'Este horário já passou, escolha outro!';
    exit();
}

// validar horario
$query = $pdo->prepare("SELECT * FROM agendamentos where data = :data and hora = :hora and funcionario = :funcionario");
$query->execute(array(':data' => $data, ':hora' => $hora, ':funcionario' => $funcionario));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if ($total_reg > 0) {
    echo 'Este horário não está disponível!';
    exit();
}

// Demonstracao: encerra aqui, sem gravar cliente nem agendamento
if ($bb_demo) {
    echo 'Agendado com Sucesso';
    exit;
}

// Cadastrar o cliente caso não tenha cadastro
$query = $pdo->prepare("SELECT * FROM clientes where telefone LIKE :telefone");
$query->execute(array(':telefone' => $telefone));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if (@count($res) == 0) {
    $query = $pdo->prepare("INSERT INTO clientes SET nome = :nome, telefone = :telefone, data_cad = curDate(), cartoes = '0', alertado = 'Não'");
    $query->execute(array(':nome' => $nome, ':telefone' => $telefone));
    $id_cliente = $pdo->lastInsertId();
} else {
    $id_cliente = $res[0]['id'];
}

$query = $pdo->prepare("INSERT INTO agendamentos SET funcionario = :funcionario, cliente = :id_cliente, hora = :hora, data = :data, usuario = '0', status = 'Aguardando', obs = :obs, data_lanc = curDate(), servico = :servico, servicos = :lista_servicos");
$query->execute(array(':funcionario' => $funcionario, ':id_cliente' => $id_cliente, ':hora' => $hora, ':data' => $data, ':obs' => $obs, ':servico' => $servico, ':lista_servicos' => $lista_servicos));

bb_rate_limit_publico_conta('agendamento', 3600);
echo 'Agendado com Sucesso';
?>
