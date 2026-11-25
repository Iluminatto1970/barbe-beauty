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
                <a class="navbar-brand" href="../index.php"><?php echo $nome_sistema ?></a>
                <div class="ml-auto">
                    <a title="Voltar para o Site" class="nav-link text-white" href="../index.php">
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
                                <a class="recuperar-link" href="" data-toggle="modal" data-target="#exampleModal">
                                    <i class="fa fa-key mr-1"></i>Esqueceu sua senha?
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

</body>
</html>




<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="max-width:400px;width:100%">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Recuperar Senha</h5>
        <button id="btn-fechar-rec" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true" >&times;</span>
        </button>
      </div>
      <form method="post" id="form-recuperar">
      <div class="modal-body">
        
        	<input placeholder="Digite seu Email" class="form-control" type="email" name="email" id="email-recuperar" required>        	
       
       <br>
       <small><div id="mensagem-recuperar" align="center"></div></small>
      </div>
      <div class="modal-footer">      
        <button type="submit" class="btn btn-primary">Recuperar</button>
      </div>
  <?php echo bb_csrf_field(); ?></form>
    </div>
  </div>
</div>



<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>


 <script type="text/javascript">
	$("#form-recuperar").submit(function () {

		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: "recuperar-senha.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-recuperar').text('');
				$('#mensagem-recuperar').removeClass()
				if (mensagem.trim() == "Recuperado com Sucesso") {
					//$('#btn-fechar-rec').click();					
					$('#email-recuperar').val('');
					$('#mensagem-recuperar').addClass('text-success')
					$('#mensagem-recuperar').text('Sua Senha foi enviada para o Email')			

				} else {

					$('#mensagem-recuperar').addClass('text-danger')
					$('#mensagem-recuperar').text(mensagem)
				}


			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>



