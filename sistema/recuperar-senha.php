<?php
// ============================================================
// RECUPERAR SENHA
// ------------------------------------------------------------
// 1) pedido: informe o e-mail e recebe um link de uso unico;
// 2) link: o token abre o formulario de nova senha;
// 3) ao salvar, a senha e trocada e o link deixa de valer.
//
// A resposta do pedido e sempre a mesma, exista ou nao a conta,
// para nao revelar quais e-mails estao cadastrados.
// ============================================================
require_once(__DIR__ . '/conexao.php');
require_once(__DIR__ . '/email.php');
require_once(__DIR__ . '/recuperacao.php');

if (!function_exists('bb_rec_transporte_ok')) {
    // Ha como enviar e-mail neste servidor?
    function bb_rec_transporte_ok()
    {
        $cfg = bb_email_config();
        return $cfg['resend'] !== '' || $cfg['transporte'] === 'log';
    }
}

if (!function_exists('bb_rec_limite_pedidos')) {
    // No maximo 5 pedidos a cada 10 minutos por sessao
    function bb_rec_limite_pedidos($registrar = false)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $agora = time();
        $marcas = isset($_SESSION['bb_rec_pedidos']) && is_array($_SESSION['bb_rec_pedidos'])
            ? $_SESSION['bb_rec_pedidos'] : array();
        $marcas = array_values(array_filter($marcas, function ($t) use ($agora) {
            return (int) $t > $agora - 600;
        }));
        $atingiu = count($marcas) >= 5;
        if ($registrar && !$atingiu) {
            $marcas[] = $agora;
        }
        $_SESSION['bb_rec_pedidos'] = $marcas;
        return $atingiu;
    }
}

$rec_erro = '';
$rec_aviso = '';
$rec_token_valido = false;
$rec_nome = '';
$rec_email = '';
$rec_token = '';

if (isset($_GET['enviado'])) {
    $rec_aviso = 'Se este e-mail estiver cadastrado, enviamos um link para criar uma nova senha. O link vale 1 hora.';
} elseif (isset($_GET['limite'])) {
    $rec_erro = 'Muitos pedidos seguidos. Aguarde alguns minutos e tente de novo.';
} elseif (isset($_GET['falha'])) {
    $rec_erro = 'Não conseguimos enviar o e-mail agora. Tente novamente em instantes ou fale com o suporte do seu estabelecimento.';
}

$rec_metodo = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$rec_acao = isset($_POST['acao']) ? (string) $_POST['acao'] : '';

// ---------- 1. Definir a nova senha ----------
if ($rec_metodo === 'POST' && $rec_acao === 'redefinir') {
    $rec_token = trim((string) (isset($_POST['token']) ? $_POST['token'] : ''));
    $senha = (string) (isset($_POST['senha']) ? $_POST['senha'] : '');
    $confirma = (string) (isset($_POST['senha_conf']) ? $_POST['senha_conf'] : '');
    $registro = bb_rec_buscar($pdo, $rec_token);

    if (!$registro) {
        $rec_erro = 'Este link expirou ou já foi usado. Peça um novo link abaixo.';
    } else {
        $rec_token_valido = true;
        $rec_nome = (string) $registro['nome'];
        $rec_email = (string) $registro['email'];

        if (strlen($senha) < 6) {
            $rec_erro = 'A senha deve ter pelo menos 6 caracteres.';
        } elseif ($senha !== $confirma) {
            $rec_erro = 'A confirmação não confere com a senha digitada.';
        } else {
            try {
                $pdo->prepare("UPDATE usuarios SET senha_crip = :h, senha = '', precisa_trocar_senha = 0 WHERE id = :id")
                    ->execute(array(':h' => bb_hash_senha($senha), ':id' => (int) $registro['usuario_id']));
                bb_rec_consumir($pdo, $registro);
                header('Location: index.php?senha=redefinida');
                exit;
            } catch (Exception $e) {
                $rec_erro = 'Não foi possível salvar a nova senha. Tente novamente.';
            }
        }
    }
}

// ---------- 2. Pedir o link ----------
if ($rec_metodo === 'POST' && $rec_acao === 'pedir') {
    $rec_email = trim((string) (isset($_POST['email']) ? $_POST['email'] : ''));

    if (bb_rec_limite_pedidos(true)) {
        header('Location: recuperar-senha.php?limite=1');
        exit;
    }

    if (!bb_rec_transporte_ok()) {
        $rec_erro = 'A recuperação por e-mail ainda não está habilitada neste servidor. Fale com o suporte do seu estabelecimento.';
    } elseif ($rec_email === '' || !filter_var($rec_email, FILTER_VALIDATE_EMAIL)) {
        $rec_erro = 'Informe um e-mail válido.';
    } else {
        $usuario = false;
        try {
            $q = $pdo->prepare("SELECT id, nome, email, ativo FROM usuarios WHERE email = :e LIMIT 1");
            $q->execute(array(':e' => $rec_email));
            $usuario = $q->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $usuario = false;
        }

        if ($usuario && strtolower((string) $usuario['ativo']) === 'sim') {
            $novo = bb_rec_criar($pdo, (int) $usuario['id'], 1);
            if (!$novo) {
                error_log('[barbearia-senha] nao foi possivel gerar token para usuario ' . (int) $usuario['id']);
                header('Location: recuperar-senha.php?falha=1');
                exit;
            }
            $envio = bb_email_recuperacao_enviar(
                $usuario['email'],
                $usuario['nome'],
                bb_rec_link($novo['token']),
                $nome_sistema
            );
            if (!$envio['ok']) {
                error_log('[barbearia-senha] falha no envio da recuperacao para usuario ' . (int) $usuario['id'] . ': ' . $envio['erro']);
                header('Location: recuperar-senha.php?falha=1');
                exit;
            }
        }

        // Exista ou nao a conta, a resposta e a mesma
        header('Location: recuperar-senha.php?enviado=1');
        exit;
    }
}

// ---------- 3. Abrir o link ----------
if ($rec_metodo === 'GET' && isset($_GET['token'])) {
    $rec_token = trim((string) $_GET['token']);
    $registro = bb_rec_buscar($pdo, $rec_token);
    if ($registro) {
        $rec_token_valido = true;
        $rec_nome = (string) $registro['nome'];
        $rec_email = (string) $registro['email'];
    } else {
        $rec_erro = 'Este link expirou ou já foi usado. Peça um novo link abaixo.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar senha · <?php echo htmlspecialchars($nome_sistema); ?></title>
<style>
  :root {
    --gold: #D4AF37; --gold-light: #F0CD7F; --bg: #070707;
    --card: #141414; --line: rgba(255,255,255,.10);
    --text: #e8e8e8; --muted: #9ca3af;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    padding: 24px; background: radial-gradient(circle at 20% 0%, #1b1b1b 0%, var(--bg) 55%);
    color: var(--text); font-family: "Poppins", Arial, Helvetica, sans-serif;
  }
  .card {
    width: 100%; max-width: 420px; background: var(--card); border: 1px solid var(--line);
    border-top: 3px solid var(--gold); border-radius: 15px; padding: 30px 26px;
    box-shadow: 0 18px 50px rgba(0,0,0,.55);
  }
  .marca {
    display: block; text-align: center; font-size: 12px; letter-spacing: 1.6px;
    text-transform: uppercase; color: var(--gold-light); text-decoration: none; margin-bottom: 18px;
  }
  h1 {
    font-family: "Playfair Display", Georgia, serif; font-size: 24px; line-height: 1.25;
    margin: 0 0 8px; text-align: center; color: #fff;
  }
  .sub { margin: 0 0 20px; font-size: 13.5px; line-height: 1.6; color: var(--muted); text-align: center; }
  label { display: block; font-size: 12.5px; color: var(--muted); margin-bottom: 6px; }
  input[type=email], input[type=password] {
    width: 100%; padding: 12px 14px; margin-bottom: 16px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,.16); background: #0d0d0d; color: var(--text);
    font-size: 15px; font-family: inherit;
  }
  input:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212,175,55,.16); }
  button {
    width: 100%; padding: 13px 18px; border: 0; border-radius: 8px; cursor: pointer;
    background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: #171204;
    font-size: 14px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; font-family: inherit;
  }
  button:hover { filter: brightness(1.05); }
  .aviso { margin: 0 0 18px; padding: 12px 14px; border-radius: 10px; font-size: 13px; line-height: 1.6; }
  .aviso.ok { background: rgba(52,211,153,.10); border: 1px solid rgba(52,211,153,.35); color: #c7f5e4; }
  .aviso.erro { background: rgba(248,113,113,.10); border: 1px solid rgba(248,113,113,.35); color: #ffd7d7; }
  .voltar { display: block; text-align: center; margin-top: 18px; font-size: 13px; color: var(--muted); text-decoration: underline; }
  .voltar:hover { color: var(--gold-light); }
  .dica { margin: 4px 0 0; font-size: 12px; line-height: 1.6; color: #8b9099; text-align: center; }
  .conta { font-size: 13px; color: var(--gold-light); text-align: center; margin: 0 0 16px; word-break: break-word; }
</style>
</head>
<body>
<main class="card">
  <a class="marca" href="index.php"><?php echo htmlspecialchars($nome_sistema); ?></a>

  <?php if ($rec_aviso !== '') { ?>
    <div class="aviso ok"><?php echo htmlspecialchars($rec_aviso); ?></div>
  <?php } ?>
  <?php if ($rec_erro !== '') { ?>
    <div class="aviso erro"><?php echo htmlspecialchars($rec_erro); ?></div>
  <?php } ?>

  <?php if ($rec_token_valido) { ?>
    <h1>Criar nova senha</h1>
    <?php if ($rec_email !== '') { ?>
      <p class="conta"><?php echo htmlspecialchars($rec_nome . ' · ' . $rec_email); ?></p>
    <?php } ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="acao" value="redefinir">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($rec_token); ?>">
      <label for="senha">Nova senha</label>
      <input type="password" id="senha" name="senha" minlength="6" required autofocus autocomplete="new-password">
      <label for="senha_conf">Confirmar nova senha</label>
      <input type="password" id="senha_conf" name="senha_conf" minlength="6" required autocomplete="new-password">
      <button type="submit">Salvar nova senha</button>
    </form>
    <a class="voltar" href="index.php">Voltar para o login</a>
  <?php } else { ?>
    <h1><?php echo $rec_erro !== '' ? 'Pedir novo link' : 'Recuperar senha'; ?></h1>
    <p class="sub">Informe o e-mail cadastrado no seu acesso. Enviamos um link para você criar uma nova senha.</p>
    <form method="post" autocomplete="off">
      <input type="hidden" name="acao" value="pedir">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" required autocomplete="email"
             value="<?php echo htmlspecialchars($rec_email); ?>" placeholder="voce@exemplo.com">
      <button type="submit">Enviar link</button>
    </form>
    <p class="dica">O link vale por 1 hora e só pode ser usado uma vez.</p>
    <a class="voltar" href="index.php">Voltar para o login</a>
  <?php } ?>
</main>
</body>
</html>
