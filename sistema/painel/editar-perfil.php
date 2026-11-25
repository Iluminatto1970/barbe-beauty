<?php 
require_once('../conexao.php');
bb_csrf_check_request();

$id = (int)@$_POST['id'];
$nome = trim((string)@$_POST['nome']);
$email = trim((string)@$_POST['email']);
$telefone = (string)@$_POST['telefone'];
$cpf = (string)@$_POST['cpf'];
$senha = (string)@$_POST['senha'];
$conf_senha = (string)@$_POST['conf_senha'];
$endereco = (string)@$_POST['endereco'];
$atendimento = (string)@$_POST['atendimento'];

$foto = '';

if($senha != $conf_senha){
	echo 'As senhas são diferentes!!';
	exit();
}

//validar email
$st = $pdo->prepare("SELECT * from usuarios where email = :email");
$st->execute(array(':email' => $email));
$res = $st->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 and $id != $res[0]['id']){
	echo 'Email já Cadastrado, escolha outro!!';
	exit();
}

//validar cpf
$st = $pdo->prepare("SELECT * from usuarios where cpf = :cpf");
$st->execute(array(':cpf' => $cpf));
$res = $st->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 and $id != $res[0]['id']){
	echo 'CPF já Cadastrado, escolha outro!!';
	exit();
}



//validar troca da foto
$st = $pdo->prepare("SELECT * FROM usuarios where id = :id");
$st->execute(array(':id' => $id));
$res = $st->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['foto'];
}else{
	$foto = 'sem-foto.jpg';
}


//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$nome_img = date('d-m-Y H:i:s') .'-'.@$_FILES['foto']['name'];
$nome_img = preg_replace('/[ :]+/' , '-' , $nome_img);

$caminho = 'img/perfil/' .$nome_img;

$imagem_temp = @$_FILES['foto']['tmp_name']; 

if(@$_FILES['foto']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto']);
	if ($erro_img !== '') { echo $erro_img; exit(); }

	//EXCLUO A FOTO ANTERIOR
	if($foto != "sem-foto.jpg"){
		@unlink('img/perfil/'.$foto);
	}

	$foto = $nome_img;

	bb_imagem_salvar_upload($imagem_temp, $caminho, 800);
}




$senha_crip = ($senha !== '') ? bb_hash_senha($senha) : $res[0]['senha_crip'];
$st = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, telefone = :telefone, cpf = :cpf, senha = '', senha_crip = :senha_crip, endereco = :endereco, foto = :foto, atendimento = :atendimento WHERE id = :id");
$st->bindValue(":nome", $nome);
$st->bindValue(":email", $email);
$st->bindValue(":telefone", $telefone);
$st->bindValue(":cpf", $cpf);
$st->bindValue(":senha_crip", $senha_crip);
$st->bindValue(":endereco", $endereco);
$st->bindValue(":foto", $foto);
$st->bindValue(":atendimento", $atendimento);
$st->bindValue(":id", $id, PDO::PARAM_INT);
$st->execute();

echo 'Editado com Sucesso';
 ?>