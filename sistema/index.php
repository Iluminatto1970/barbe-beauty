<?php 
// Garante a barra final em /sistema, para que o formulario de login (action
// relativo) e os CSS referenciados por caminhos relativos sejam encontrados.
$uri_requisitada = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if ($uri_requisitada != '' and $uri_requisitada !== '/' and substr($uri_requisitada, -1) !== '/' and !preg_match('/\.[a-z0-9]+$/i', $uri_requisitada)) {
	$destino_uri = $uri_requisitada . '/';
	if (!empty($_SERVER['QUERY_STRING'])) {
		$destino_uri .= '?' . $_SERVER['QUERY_STRING'];
	}
	header('Location: ' . $destino_uri, true, 301);
	exit;
}

require_once("conexao.php");
require_once("google.php");

$google_indisponivel = isset($_GET['google']) && $_GET['google'] === 'indisponivel';
$google_erro = isset($_GET['google']) && $_GET['google'] === 'erro';

//INSERIR UM USUÁRIO ADMINISTRADOR CASO NÃO EXISTA
//Somente na instalacao principal: cada cliente recebe o admin no provisionamento
if (bb_banco_atual() === bb_banco_controle()) {
	$senha = '123';
	$senha_crip = bb_hash_senha($senha);

	$query = $pdo->query("SELECT * from usuarios where nivel = 'Administrador'");
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);
	if($total_reg == 0){
		$pdo->prepare("INSERT INTO usuarios SET nome = 'Admin', email = :e, cpf = '000.000.000-00', senha = '', senha_crip = :h, nivel = 'Administrador', data = curDate(), ativo = 'Sim', foto = 'sem-foto.jpg'")
			->execute(array(':e' => $email_sistema, ':h' => $senha_crip));
	}
}

// Garante conta de teste admin@admin / 123 (idempotente)
if (bb_banco_atual() === bb_banco_controle() && bb_permitir_usuarios_teste()) {
	$hash_123 = bb_hash_senha('123');
	foreach (array('admin@admin') as $email_teste) {
		$st = $pdo->prepare("SELECT id, senha_crip, demo FROM usuarios WHERE email = :e LIMIT 1");
		$st->bindValue(':e', $email_teste);
		$st->execute();
		$row = $st->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			$pdo->prepare("INSERT INTO usuarios SET nome = 'Admin', email = :e, cpf = '000.000.000-00', senha = '', senha_crip = :h, nivel = 'Administrador', data = curDate(), ativo = 'Sim', foto = 'sem-foto.jpg', demo = 1")
				->execute(array(':e' => $email_teste, ':h' => $hash_123));
		} elseif (!bb_checar_senha('123', $row['senha_crip'])['ok']) {
			$up = $pdo->prepare("UPDATE usuarios SET senha_crip = :h, senha = '', demo = 1 WHERE id = :id");
			$up->execute(array(':h' => $hash_123, ':id' => $row['id']));
		} elseif ((int) $row['demo'] !== 1) {
			// Conta de teste/demo nunca tem poder de escrita, mesmo se ja existir
			$pdo->prepare("UPDATE usuarios SET demo = 1 WHERE id = :id")->execute(array(':id' => $row['id']));
		}
	}
}


// Seguranca: a conta demo admin@admin/123 nunca tem poder de escrita fora de
// localhost. Com outro Administrador ativo ela segue ativa apenas como
// demonstracao (leitura); se for o unico admin, e desativada, para nao deixar
// o sistema com uma credencial publica de poder total.
if (bb_banco_atual() === bb_banco_controle() && !bb_permitir_usuarios_teste()) {
	$st_demo = $pdo->prepare("SELECT id, demo FROM usuarios WHERE email = 'admin@admin' AND ativo = 'Sim' LIMIT 1");
	$st_demo->execute();
	$demo = $st_demo->fetch(PDO::FETCH_ASSOC);
	if ($demo) {
		$st_outros = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nivel = 'Administrador' AND ativo = 'Sim' AND id <> :id");
		$st_outros->execute(array(':id' => $demo['id']));
		if ((int) $st_outros->fetchColumn() > 0) {
			// Ha outro Administrador: a demo fica ativa, porem somente leitura.
			if ((int) $demo['demo'] !== 1) {
				$pdo->prepare("UPDATE usuarios SET demo = 1 WHERE id = :id")
					->execute(array(':id' => $demo['id']));
			}
		} else {
			// Unico Administrador: desativar evita credencial publica com poder total.
			$pdo->prepare("UPDATE usuarios SET ativo = 'Não', precisa_trocar_senha = 1 WHERE id = :id")
				->execute(array(':id' => $demo['id']));
		}
	}
}

$query = $pdo->query("SELECT * from cargos");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg == 0){
	$pdo->query("INSERT INTO cargos SET nome = 'Administrador'");
}



 ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<title><?php echo $nome_sistema ?></title>
	
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" type="text/css" href="../css/bootstrap.css" />
	
	<!-- Fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
	
	<!-- Font Awesome -->
	<link href="../css/font-awesome.min.css" rel="stylesheet" />
	
	<!-- Custom CSS -->
	<link rel="stylesheet" type="text/css" href="<?php echo ($segmento == 'salao') ? 'css/estilo-login-salao.css' : 'css/estilo-login.css'; ?>">
	<link rel="icon" type="image/png" href="../images/<?php echo $icone_site ?>">
	
	<!-- jQuery -->
	<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
	<!-- Bootstrap JS -->
	<script src="../js/bootstrap.js"></script>
</head>
<body>

<div class="hero_area">
    <div class="hero_bg_box">
        <img src="../images/<?php echo $img_banner_index ?>" alt="">
    </div>
    
    <!-- Header section -->
    <header class="header_section">
        <div class="container">
            <nav class="navbar navbar-expand-lg custom_nav-container">
                <a class="navbar-brand" href="../index.php?site=1"><?php echo $nome_sistema ?></a>
                <div class="ml-auto">
                    <a title="Voltar para o Site" class="nav-link text-white" href="../index.php?site=1">
                        <i class="fa fa-home" aria-hidden="true"></i> Voltar ao Site
                    </a>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Login section -->
    <section class="login_section">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-6 col-lg-5">
                    <div class="login_container">
                        <div class="login_form">
                            <?php if (isset($_GET['senha']) && $_GET['senha'] === 'redefinida') { ?>
                                <div class="alert alert-success py-2 px-3" style="font-size:13px">
                                    Senha alterada com sucesso. Entre com a nova senha.
                                </div>
                            <?php } ?>
                            <?php if (isset($_GET['sessao']) && $_GET['sessao'] === 'expirada') { ?>
                                <div class="alert alert-warning py-2 px-3" style="font-size:13px">
                                    Sua sessão expirou. Entre novamente para continuar.
                                </div>
                            <?php } ?>
                            <div class="heading_container text-center mb-4">
                                 <h2 class="text-white">Acesso ao Sistema</h2>
                                 <p class="text-white-50">
                                     <?php echo htmlspecialchars($nome_sistema) ?> · painel administrativo
                                 </p>
                             </div>
                            
                            <form accept-charset="UTF-8" role="form" action="autenticar.php" method="post">
	<?php echo bb_csrf_field(); ?>
                                <div class="form-group mb-3">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-user"></i>
                                            </span>
                                        </div>
                                        <input class="form-control" placeholder="E-mail ou CPF" name="email" type="text" required>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-lock"></i>
                                            </span>
                                        </div>
                                        <input class="form-control" placeholder="Senha" name="senha" type="password" required>
                                    </div>
                                </div>
                                
                                <button class="btn btn-login btn-block mb-3" type="submit">
                                    <i class="fa fa-sign-in mr-2"></i>Entrar no Sistema
                                </button>
                            <?php echo bb_csrf_field(); ?></form>

                            <?php
                            // Entrada na demonstracao: aparece quando o banco tem
                            // uma conta marcada como demo (somente leitura).
                            $bb_tem_demo = false;
                            try {
                                $bb_tem_demo = (bool) $pdo->query("SELECT 1 FROM usuarios WHERE demo = 1 AND ativo = 'Sim' LIMIT 1")->fetchColumn();
                            } catch (Exception $e) {
                                $bb_tem_demo = false;
                            }
                            if ($bb_tem_demo) { ?>
                            <div style="margin:0 0 18px;padding:14px 16px;border-radius:10px;background:rgba(240,205,127,.10);border:1px solid rgba(240,205,127,.45);text-align:center">
                                <p style="margin:0 0 4px;font-size:13px;color:#f0cd7f;font-weight:600;letter-spacing:.4px">
                                    <i class="fa fa-eye mr-1"></i> Modo demonstração
                                </p>
                                <p style="margin:0 0 10px;font-size:12.5px;color:#d5d9df;line-height:1.55">
                                    Entre como Administrador e explore o painel com dados fictícios. Nenhuma alteração é salva e nenhum registro real é exibido.
                                </p>
                                <a class="btn btn-block" href="demo.php" style="background:#f0cd7f;color:#1b1b1b;font-weight:700;border-radius:8px;padding:10px 14px;text-decoration:none">
                                    <i class="fa fa-play-circle mr-2"></i>Entrar na demonstração
                                </a>
                            </div>
                            <?php } ?>

                            <?php if (bb_google_configurado()) { ?>
                                <a class="btn btn-block mb-3" href="google-login.php"
                                   style="display:flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#3c4043;border:1px solid #dadce0;border-radius:6px;padding:11px 14px;font-weight:600;text-decoration:none;">
                                    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                    </svg>
                                    Entrar com Google
                                </a>
                            <?php } elseif ($google_indisponivel) { ?>
                                <div class="alert alert-warning py-2 px-3" style="font-size:13px">
                                    Login com Google ainda não configurado neste estabelecimento. Entre com e-mail e senha.
                                </div>
                            <?php } ?>

                            <?php if ($google_erro) { ?>
                                <div class="alert alert-danger py-2 px-3" style="font-size:13px">
                                    Não foi possível entrar com o Google. Tente novamente ou use e-mail e senha.
                                </div>
                            <?php } ?>
                            
                            <div class="text-center">
                                <a class="recuperar-link" href="recuperar-senha.php">
                                    <i class="fa fa-key mr-1"></i>Esqueceu sua senha?
                                </a>
                            </div>

                            <?php
                            // Area de cadastro de novos estabelecimentos: existe apenas
                            // na instalacao principal, ou seja, quando a requisicao e
                            // servida pelo banco de controle. No banco de um cliente o
                            // site e o painel sao dele, sem oferta do SaaS.
                            if (bb_instalacao_principal()) {
                                $bb_login_gratis = null;
                                $bb_login_pago = null;
                                try {
                                    $bb_login_q = $pdo->query("SELECT * FROM saas_planos WHERE ativo = 'Sim' ORDER BY valor ASC");
                                    if ($bb_login_q) {
                                        foreach ($bb_login_q->fetchAll(PDO::FETCH_ASSOC) as $bb_login_p) {
                                            if ((float) $bb_login_p['valor'] <= 0) {
                                                if ($bb_login_gratis === null) {
                                                    $bb_login_gratis = $bb_login_p;
                                                }
                                            } elseif ($bb_login_pago === null) {
                                                $bb_login_pago = $bb_login_p;
                                            }
                                        }
                                    }
                                } catch (Exception $e) {
                                    // tabela ausente: usa os textos padrao
                                }
                                $bb_login_limite = ($bb_login_gratis && (int) $bb_login_gratis['limite_agendamentos'] > 0)
                                    ? (int) $bb_login_gratis['limite_agendamentos']
                                    : null;
                                $bb_login_preco = $bb_login_pago ? (float) $bb_login_pago['valor'] : 49.00;
                            ?>
                            <div style="margin-top:22px;padding-top:20px;border-top:1px solid rgba(255,255,255,.14)">
                                <p class="text-center" style="font-size:11.5px;letter-spacing:.9px;text-transform:uppercase;margin-bottom:12px;color:#9ca3af">
                                    Ainda não tem conta?
                                </p>
                                <a class="btn btn-login btn-block" href="../assinar.php" style="text-decoration:none;font-size:13px;white-space:normal">
                                    <i class="fa fa-plus-circle mr-2"></i>Criar minha conta grátis
                                </a>
                                <div style="margin-top:14px;padding:12px 14px;background:rgba(0,0,0,.32);border:1px solid rgba(255,255,255,.12);border-radius:10px;font-size:13px;color:#e5e7eb;line-height:1.65;text-align:left">
                                    <strong style="color:#f0cd7f"><?php echo htmlspecialchars($bb_login_gratis ? $bb_login_gratis['nome'] : 'Grátis'); ?>:</strong>
                                    <?php echo $bb_login_limite !== null
                                        ? 'até ' . (int) $bb_login_limite . ' agendamentos por mês, sem cartão.'
                                        : 'agendamentos ilimitados, sem cartão.'; ?><br>
                                    <strong style="color:#f0cd7f"><?php echo htmlspecialchars($bb_login_pago ? $bb_login_pago['nome'] : 'PRO'); ?>:</strong>
                                    <?php echo 'R$ ' . number_format($bb_login_preco, 2, ',', '.'); ?>/mês com agendamentos ilimitados.
                                </div>
                                <div class="text-center" style="margin-top:12px">
                                    <a href="../planos.php" style="font-size:12.5px;color:#c3c9d1;text-decoration:underline">Ver planos e todos os recursos</a>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

</body>
</html>
