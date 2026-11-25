<?php 
require_once("../../../conexao.php");
bb_csrf_check_request();
$tabela = 'usuarios';
if (!bb_tabela_permitida($tabela)) { echo 'Tabela inválida'; exit(); }

$id = $_POST['id'];
$nome = $_POST['nome'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];
$cpf = $_POST['cpf'];
$cargo = $_POST['cargo'];
$endereco = $_POST['endereco'];
$atendimento = $_POST['atendimento'];
$senha = '123';
$senha_crip = bb_hash_senha($senha);

if($cargo == "0"){
	echo 'Cadastre um Cargo para o Usuário';
	exit();
}

//validar email
$query = $pdo->prepare("SELECT * from $tabela where email = :email");
$query->bindValue(':email', $email);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 and $id != $res[0]['id']){
	echo 'Email já Cadastrado, escolha outro!!';
	exit();
}


//validar cpf
$query = $pdo->prepare("SELECT * from $tabela where cpf = :cpf");
$query->bindValue(':cpf', $cpf);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0 and $id != $res[0]['id']){
	echo 'CPF já Cadastrado, escolha outro!!';
	exit();
}




//validar troca da foto
$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->bindValue(':id', $id);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['foto'];
}else{
	$foto = 'sem-foto.jpg';
}


//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$nome_img = date('d-m-Y H:i:s') .'-'.@$_FILES['foto']['name'];
$nome_img = preg_replace('/[ :]+/' , '-' , $nome_img);

$caminho = __DIR__ . '/../../img/perfil/' .$nome_img;

$imagem_temp = @$_FILES['foto']['tmp_name']; 

if(@$_FILES['foto']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto']);
	if ($erro_img !== '') { echo $erro_img; exit(); }

	// Grava a foto nova antes de trocar o cadastro: se o envio falhar, o
	// usuario continua com a foto antiga em vez de uma referencia quebrada.
	if (!bb_imagem_salvar_upload($imagem_temp, $caminho, 800) || !is_file($caminho)) {
		echo 'Não foi possível salvar a foto. Tente novamente com um JPG ou PNG de até 10MB.';
		exit();
	}

	//EXCLUO A FOTO ANTERIOR
	if($foto != "" && $foto != "sem-foto.jpg" && is_file(__DIR__ . '/../../img/perfil/'.$foto)){
		@unlink(__DIR__ . '/../../img/perfil/'.$foto);
	}

	$foto = $nome_img;
}




if($id == ""){
	$query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, email = :email, cpf = :cpf, senha = '', senha_crip = :senha_crip, nivel = :cargo, data = curDate(), ativo = 'Sim', telefone = :telefone, endereco = :endereco, foto = :foto, atendimento = :atendimento");
$query->bindValue(':atendimento', $atendimento);
$query->bindValue(':cargo', $cargo);
$query->bindValue(':foto', $foto);
$query->bindValue(':senha_crip', $senha_crip);
}else{
	$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, email = :email, cpf = :cpf, nivel = :cargo, telefone = :telefone, endereco = :endereco, foto = :foto, atendimento = :atendimento WHERE id = :id");
$query->bindValue(':atendimento', $atendimento);
$query->bindValue(':cargo', $cargo);
$query->bindValue(':foto', $foto);
$query->bindValue(':id', $id);
}

$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":telefone", "$telefone");
$query->bindValue(":endereco", "$endereco");
$query->execute();

echo 'Salvo com Sucesso';
 ?>