<?php
require_once("../sistema/conexao.php");

$bb_csrf = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!bb_csrf_validate($bb_csrf)) {
    bb_resposta_texto(403, 'Sessão expirada. Recarregue a página e tente novamente.');
}

$bb_espera = bb_rate_limit_publico_espera('contato', 5, 3600);
if ($bb_espera > 0) {
    bb_resposta_texto(429, 'Muitas mensagens em pouco tempo. Tente novamente mais tarde.');
}

$nome = trim((string) @$_POST['nome']);
$telefone = trim((string) @$_POST['telefone']);
$email = trim((string) @$_POST['email']);
$mensagem = trim((string) @$_POST['mensagem']);

if ($nome === '' || mb_strlen($nome, 'UTF-8') > 100) {
    echo 'Informe um nome válido.';
    exit();
}

if ($telefone === '' || mb_strlen($telefone, 'UTF-8') > 20) {
    echo 'Informe um telefone válido.';
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 120) {
    echo 'Informe um e-mail válido.';
    exit();
}

if ($mensagem === '' || mb_strlen($mensagem, 'UTF-8') > 1000) {
    echo 'Escreva uma mensagem de até 1000 caracteres.';
    exit();
}

$remetente = $email_sistema;
$assunto = 'Contato - ' . $nome_sistema;

$corpo = "Nome: " . $nome . "\r\n"
    . "Telefone: " . $telefone . "\r\n"
    . "E-mail: " . $email . "\r\n\r\n"
    . "Mensagem:\r\n" . $mensagem;

// O remetente e sempre o e-mail do estabelecimento; o visitante vai no
// Reply-To. Nunca usar o e-mail digitado no From (injeção de cabeçalho).
$cabecalhos = "From: " . $remetente . "\r\n"
    . "Reply-To: " . $email . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8";

$enviado = @mail($remetente, $assunto, $corpo, $cabecalhos);

if ($enviado) {
    bb_rate_limit_publico_conta('contato', 3600);
    echo 'Enviado com Sucesso';
} else {
    echo 'Nao foi possivel enviar o e-mail. Configure um servidor de e-mail (SMTP) no PHP deste ambiente.';
}
?>
