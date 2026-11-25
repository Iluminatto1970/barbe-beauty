<?php 
@session_start();
require_once("conexao.php");
bb_csrf_check_request();


$email = trim((string) @$_POST['email']);
$senha = (string) @$_POST['senha'];

// Rate limit (anti brute-force) por IP
$wait = bb_rate_limit_login();
if ($wait > 0) {
	echo "<script>window.alert('Muitas tentativas de login. Tente novamente em " . (int)ceil($wait / 60) . " minuto(s).')</script>";
	echo "<script>window.location='index.php'</script>";
	exit;
}

// Rate limit por conta (anti brute-force por email)
$waitC = bb_rate_limit_conta($email);
if ($waitC > 0) {
	echo "<script>window.alert('Conta temporariamente bloqueada. Tente novamente em " . (int)ceil($waitC / 60) . " minuto(s).')</script>";
	echo "<script>window.location='index.php'</script>";
	exit;
}

$query = $pdo->prepare("SELECT * from usuarios where (email = :email or cpf = :email) limit 1");
$query->bindValue(":email", $email);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

$total_reg = 0;
if (@count($res) > 0) {
	$checagem = bb_checar_senha($senha, $res[0]['senha_crip']);
	if ($checagem['ok']) {
		$total_reg = 1;

		// Migracao transparente: MD5 (ou hash antigo) vira password_hash
		if ($checagem['rehash']) {
			$novo_hash = bb_hash_senha($senha);
			// Flag: se veio de MD5 legado, forca troca de senha (sunset)
			$era_md5 = preg_match('/^[a-f0-9]{32}$/i', (string)$res[0]['senha_crip']);
			$flag = $era_md5 ? 1 : 0;
			$up = $pdo->prepare("UPDATE usuarios SET senha_crip = :hash, senha = '', precisa_trocar_senha = :flag WHERE id = :id");
			$up->bindValue(":hash", $novo_hash);
			$up->bindValue(":flag", $flag, PDO::PARAM_INT);
			$up->bindValue(":id", $res[0]['id']);
			$up->execute();
			$res[0]['precisa_trocar_senha'] = $flag;
		}
	}
}

if ($total_reg > 0) {
	bb_rate_limit_login_ok();
	bb_rate_limit_conta_ok($email);
	$ativo = $res[0]['ativo'];


	if($ativo == 'Sim'){

		// Bloqueia estabelecimentos com assinatura pendente/suspensa/vencida
		$bloqueio = bb_tenant_bloqueio();
		if ($bloqueio !== '') {
			echo "<script>window.alert(" . json_encode($bloqueio) . ")</script>";
			echo "<script>window.location='index.php'</script>";
			exit;
		}

		session_regenerate_id(true);
		$_SESSION['id'] = $res[0]['id'];
		$_SESSION['nivel'] = $res[0]['nivel'];
		$_SESSION['nome'] = $res[0]['nome'];
		// Amarra a sessao ao banco do estabelecimento resolvido pelo dominio
		$_SESSION['bb_tenant_db'] = bb_banco_atual();
		// Conta de demonstracao: entra como Administrador, mas sem poder de escrita
		$_SESSION['bb_demo'] = (isset($res[0]['demo']) && (int) $res[0]['demo'] === 1) ? 1 : 0;

		// Sunset MD5: forca troca de senha se migrado de MD5
		$precisa = (int)@$res[0]['precisa_trocar_senha'];
		if ($precisa === 1) {
			echo "<script>window.location='trocar-senha.php'</script>";
		} else {
			//ir para o painel
			echo "<script>window.location='painel'</script>";
		}
	}else{
		echo "<script>window.alert('Seu usuário foi desativado, contate o administrador!')</script>";
	echo "<script>window.location='index.php'</script>";
	}
	
} else {
	bb_rate_limit_login_fail();
	bb_rate_limit_conta_fail($email);
	echo "<script>window.alert('Usuário ou Senha Incorretos!')</script>";
	echo "<script>window.location='index.php'</script>";
}

 ?>