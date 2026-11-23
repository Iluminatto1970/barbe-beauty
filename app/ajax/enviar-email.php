<?php 
require_once("../sistema/conexao.php");

$remetente = $email_sistema;
$assunto = 'Contato - ' .$nome_sistema;

$mensagem = utf8_decode('Nome: '.$_POST['nome']. "\r\n"."\r\n" . 'Telefone: '.$_POST['telefone']. "\r\n"."\r\n" . 'Mensagem: ' . "\r\n"."\r\n" .$_POST['mensagem']);
$dest = $_POST['email'];
$cabecalhos = "From: " .$dest;

$enviado = @mail($remetente, $assunto, $mensagem, $cabecalhos);

if($enviado){
	echo 'Enviado com Sucesso';
}else{
	echo 'Nao foi possivel enviar o e-mail. Configure um servidor de e-mail (SMTP) no PHP deste ambiente.';
}

 ?>