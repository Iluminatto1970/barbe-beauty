<?php
require_once("sistema/conexao.php");

// Tema escolhido pelo visitante (cookie) prevalece sobre o segmento configurado.
// Assim a landing pode oferecer os dois temas sem alterar a configuracao do negocio.
$segmento_site = $segmento;
if (isset($_COOKIE['bb_segmento']) && in_array($_COOKIE['bb_segmento'], array('barbearia', 'salao'))) {
  $segmento_site = $_COOKIE['bb_segmento'];
}

if (isset($_GET['segmento']) && in_array($_GET['segmento'], array('barbearia', 'salao'))) {
  $segmento_site = $_GET['segmento'];
  setcookie('bb_segmento', $segmento_site, time() + 31536000, '/');
  $destino_tema = strtok($_SERVER['REQUEST_URI'], '?');
  header('Location: ' . $destino_tema, true, 302);
  exit;
}

$segmento = $segmento_site; // tema efetivo para as paginas seguintes
bb_aplicar_segmento($segmento); // troca nome, imagens e textos do segmento escolhido
?>
<!DOCTYPE html>
<html>

<head>
  <!-- Basic -->
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- Mobile Metas -->
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <!-- Site Metas -->
  <meta name="keywords" content="<?php echo $segmento == 'salao' ? 'salao de beleza, cabeleireiro, estetica, beleza' : 'barbearia, corte masculino, barba'; ?>" />
  <meta name="description" content="Fazemos todo tipo de serviço ..." />
  <meta name="author" content="<?php echo ($segmento == 'salao') ? 'Salão de Beleza' : 'Barbearia'; ?>" />
  <link rel="shortcut icon" href="images/<?php echo $icone_site ?>" type="image/x-icon">

  <!-- Aplica o tema salvo antes da primeira pintura (evita flash) -->
  <script>
    (function () {
      var t = 'escuro';
      try { t = localStorage.getItem('bb_tema') || 'escuro'; } catch (e) {}
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>

  <title><?php echo $nome_sistema ?></title>

  <!-- bootstrap core css -->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />

  <!-- fonts style -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <!--owl slider stylesheet -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <!-- font awesome style -->
  <link href="css/font-awesome.min.css" rel="stylesheet" />

  <!-- Custom styles for this template -->
  <?php if($segmento == 'salao'){ ?>
  <!-- Tema Salao Premium -->
  <link href="css/style-salao.css" rel="stylesheet" />
  <?php }else{ ?>
  <link href="css/style.css" rel="stylesheet" />
  <?php } ?>
  <!-- responsive style -->
  <link href="css/responsive.css" rel="stylesheet" />
</head>

<?php 
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$body_class = ($current_page == 'index') ? '' : 'sub_page';
?>
<body class="<?php echo trim($body_class . ($segmento == 'salao' ? ' tema-salao' : '')); ?>">
  <style>
    /* Selo de demonstracao: o site, o agendamento e o painel sao exemplos */
    .bb-faixa-demo { background: #0f1720; color: #e6eaed; font-size: 12.5px; line-height: 1.5; padding: 8px 14px; text-align: center; }
    .bb-faixa-demo .container { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 2px 10px; }
    .bb-faixa-demo strong { color: #d9b26a; font-size: 11px; letter-spacing: 1.3px; text-transform: uppercase; }
    .bb-faixa-demo a { color: #d9b26a; font-weight: 600; text-decoration: none; border-bottom: 1px solid rgba(217,178,106,.5); }
    .bb-faixa-demo a:hover { color: #f0cd7f; }
    @media (max-width: 720px) {
      .bb-faixa-demo { font-size: 11.5px; padding: 7px 10px; }
      .bb-faixa-demo .container { gap: 1px 8px; }
      .bb-faixa-demo a { white-space: nowrap; }
    }
  </style>
  <div class="bb-faixa-demo" role="note">
    <div class="container">
      <span><strong>Demonstração</strong> — este site é um exemplo do sistema Barbearia PRO: o agendamento e o painel são simulados.</span>
      <a href="assinar.php">Criar a conta do meu estabelecimento</a>
    </div>
  </div>
  <div class="hero_area">
    <div class="hero_bg_box">
      <img src="images/<?php echo $img_banner_index ?>" alt="">
    </div>
    <!-- header section strats -->
    <header class="header_section">
      <div class="container">
        <nav class="navbar navbar-expand-lg custom_nav-container ">
          <a class="navbar-brand " href="index"> <?php echo $nome_sistema ?> </a>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class=""> </span>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav  ">
              <li class="nav-item active">
                <a class="nav-link" href="index">Home <span class="sr-only">(current)</span></a>
              </li>
               <li class="nav-item">
                <a class="nav-link" href="agendamentos"> Agendamentos</a>
              </li>           
              <li class="nav-item">
                <a class="nav-link" href="produtos">Produtos</a>
              </li>
               <li class="nav-item">
                <a class="nav-link" href="servicos">Serviços</a>
              </li>
             
             
              <li class="nav-item">
                <button id="btn-tema" class="tema-toggle" type="button" title="Alternar tema" aria-label="Alternar tema escuro/claro">
                  <i class="tema-icone fa fa-moon-o" aria-hidden="true"></i>
                </button>
              </li>

              <li class="nav-item">
                <a title="Ir para o Sistema" class="nav-link" href="sistema" target="_blank"> <i class="fa fa-user" aria-hidden="true"></i> </a>
              </li>

                <li  class="nav-item">
                <a title="Ir para o Whatsapp" class="nav-link" href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>" target="_blank"> <i class="fa fa-whatsapp" aria-hidden="true"></i> </a>
              </li>

               <li class="nav-item">
                <a title="Ver Instagram" class="nav-link" href="<?php echo $instagram_sistema ?>" target="_blank"> <i class="fa fa-instagram" aria-hidden="true"></i> </a>
              </li>
             
            </ul>
          </div>
        </nav>
      </div>
    </header>
    <!-- end header section -->