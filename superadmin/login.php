<?php
require_once(__DIR__ . '/_auth.php');

if (sa_logado()) {
    header('Location: index.php');
    exit;
}

$erro = isset($_GET['erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin · Painel SaaS</title>
    <link rel="stylesheet" href="css/superadmin.css">
</head>
<body>
    <div class="login-box">
        <h1>⚙️ Super Admin</h1>
        <p class="sub">Gestão do SaaS</p>

        <?php if ($erro) { ?>
            <div class="aviso erro">E-mail ou senha incorretos.</div>
        <?php } ?>

        <form method="post" action="autenticar.php">
            <div class="campo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autofocus autocomplete="username">
            </div>
            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required autocomplete="current-password">
            </div>
            <div class="acoes-form">
                <button type="submit" class="btn primario">Entrar</button>
            </div>
        </form>
    </div>
</body>
</html>
