<?php
@session_start();
require_once("conexao.php");

if (@$_SESSION['id'] == "") {
    echo "<script>window.location='index.php'</script>";
    exit();
}

// Conta de demonstracao tem acesso publico: a senha nao pode ser trocada.
if (bb_usuario_demo()) {
    echo "<script>window.location='painel'</script>";
    exit();
}

// Verifica flag
$st = $pdo->prepare("SELECT precisa_trocar_senha, senha_crip FROM usuarios WHERE id = :id LIMIT 1");
$st->execute(array(':id' => $_SESSION['id']));
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row || (int)$row['precisa_trocar_senha'] !== 1) {
    echo "<script>window.location='painel'</script>";
    exit();
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atual = (string)@$_POST['senha_atual'];
    $nova = (string)@$_POST['senha_nova'];
    $conf = (string)@$_POST['senha_conf'];

    $chec = bb_checar_senha($atual, $row['senha_crip']);
    if (!$chec['ok']) {
        $erro = 'Senha atual incorreta.';
    } elseif (strlen($nova) < 6) {
        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
    } elseif ($nova !== $conf) {
        $erro = 'A confirmação não confere.';
    } else {
        $hash = bb_hash_senha($nova);
        $up = $pdo->prepare("UPDATE usuarios SET senha_crip = :h, senha = '', precisa_trocar_senha = 0 WHERE id = :id");
        $up->execute(array(':h' => $hash, ':id' => $_SESSION['id']));
        echo "<script>window.alert('Senha alterada com sucesso!')</script>";
        echo "<script>window.location='painel'</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Trocar Senha</title>
<style>
/* A tela segue o segmento do estabelecimento (barbearia x salao) */
body{font-family:Arial,sans-serif;background:#f4f4f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);width:360px;max-width:90%;border-top:3px solid <?php echo ($segmento == 'salao') ? '#7C5D67' : '#D4AF37'; ?>}
h2{margin:0 0 20px;text-align:center}
label{display:block;margin:10px 0 4px;font-size:14px}
input{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box}
input:focus{outline:none;border-color:<?php echo ($segmento == 'salao') ? '#7C5D67' : '#D4AF37'; ?>}
button{width:100%;padding:10px;margin-top:16px;background:<?php echo ($segmento == 'salao') ? '#7C5D67' : '#2c3e50'; ?>;color:#fff;border:none;border-radius:4px;cursor:pointer}
.erro{color:#c0392b;margin:10px 0;font-size:14px;text-align:center}
.msg{color:#27ae60;margin:10px 0;font-size:14px;text-align:center}
</style>
</head>
<body>
<div class="box">
<h2>Troca de senha obrigatória</h2>
<p style="font-size:13px;color:#555;text-align:center">Sua senha foi migrada de um formato legado. Por segurança, defina uma nova senha.</p>
<?php if ($erro !== '') { echo '<div class="erro">' . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
<form method="post" autocomplete="off">
<label>Senha atual</label>
<input type="password" name="senha_atual" required>
<label>Nova senha</label>
<input type="password" name="senha_nova" required minlength="6">
<label>Confirmar nova senha</label>
<input type="password" name="senha_conf" required minlength="6">
<button type="submit">Alterar senha</button>
<?php echo bb_csrf_field(); ?></form>
</div>
</body>
</html>
