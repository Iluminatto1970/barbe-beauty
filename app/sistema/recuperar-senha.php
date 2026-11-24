<?php 
require_once("conexao.php");
bb_csrf_check_request();

$email = trim((string) @$_POST['email']);

$query = $pdo->prepare("SELECT * from usuarios where email = :email limit 1");
$query->bindValue(":email", $email);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	// Gera uma senha temporaria; o banco guarda apenas o hash (nunca o texto)
	$caracteres = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$senha_temp = '';
	for($i = 0; $i < 10; $i++){
		$senha_temp .= $caracteres[random_int(0, strlen($caracteres) - 1)];
	}

	//envio do email (a senha so e trocada se o envio funcionar)
	$destinatario = $email;
    $assunto = $nome_sistema . ' - Recuperação de Senha';
    $mensagem = 'Sua nova senha temporaria e: ' . $senha_temp . "\n\nEntre no sistema e altere a senha no seu perfil.";
    $cabecalhos = "From: ".$email_sistema;
   
    $enviado = @mail($destinatario, $assunto, $mensagem, $cabecalhos);

    if($enviado){
        $up = $pdo->prepare("UPDATE usuarios SET senha_crip = :hash, senha = '' WHERE id = :id");
        $up->bindValue(":hash", bb_hash_senha($senha_temp));
        $up->bindValue(":id", $res[0]['id']);
        $up->execute();

        echo 'Recuperado com Sucesso';
    }else{
        echo 'Nao foi possivel enviar o e-mail. Configure um servidor de e-mail (SMTP) no PHP deste ambiente.';
    }
}else{
	echo 'Esse email não está Cadastrado!';
}

 ?>