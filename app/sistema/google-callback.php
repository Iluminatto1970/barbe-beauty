<?php
// Retorno do Google (OAuth 2.0). Valida o state, troca o codigo por um
// token e entra na conta existente do estabelecimento atual.
@session_start();
require_once(__DIR__ . '/conexao.php');
require_once(__DIR__ . '/google.php');

function bb_google_falhar($motivo)
{
    echo "<script>window.alert(" . json_encode($motivo) . ")</script>";
    echo "<script>window.location='index.php'</script>";
    exit;
}

$state_recebido = isset($_GET['state']) ? (string) $_GET['state'] : '';
$state_salvo = isset($_SESSION['bb_google_state']) ? (string) $_SESSION['bb_google_state'] : '';
unset($_SESSION['bb_google_state']);

if ($state_salvo === '' || $state_recebido === '' || !hash_equals($state_salvo, $state_recebido)) {
    bb_google_falhar('Sessao do Google expirada. Tente entrar novamente.');
}

if (isset($_GET['error'])) {
    bb_google_falhar('Login com Google cancelado.');
}

$codigo = isset($_GET['code']) ? (string) $_GET['code'] : '';
if ($codigo === '') {
    bb_google_falhar('O Google nao retornou um codigo de acesso.');
}

$token = bb_google_trocar_codigo($codigo);
if (!$token['ok']) {
    bb_google_falhar($token['erro']);
}

$conta = bb_google_usuario($token['access_token']);
if (!$conta['ok']) {
    bb_google_falhar($conta['erro']);
}

$bloqueio = bb_tenant_bloqueio();
if ($bloqueio !== '') {
    bb_google_falhar($bloqueio);
}

$q = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(email) = :email LIMIT 1");
$q->bindValue(':email', $conta['email']);
$q->execute();
$usuario = $q->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    bb_google_falhar('Nao existe uma conta com este e-mail neste estabelecimento. Entre com e-mail e senha.');
}

if (isset($usuario['ativo']) && $usuario['ativo'] !== 'Sim') {
    bb_google_falhar('Seu usuario foi desativado, contate o administrador!');
}

session_regenerate_id(true);
$_SESSION['id']    = $usuario['id'];
$_SESSION['nivel'] = $usuario['nivel'];
$_SESSION['nome']  = $usuario['nome'];
$_SESSION['bb_tenant_db'] = bb_banco_atual();

echo "<script>window.location='painel'</script>";
