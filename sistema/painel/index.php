<?php 
// Garante a barra final em /sistema/painel, para que os links e arquivos
// referenciados por caminhos relativos sejam encontrados.
$uri_requisitada = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if ($uri_requisitada != '' and $uri_requisitada !== '/' and substr($uri_requisitada, -1) !== '/' and !preg_match('/\.[a-z0-9]+$/i', $uri_requisitada)) {
	$destino_uri = $uri_requisitada . '/';
	if (!empty($_SERVER['QUERY_STRING'])) {
		$destino_uri .= '?' . $_SERVER['QUERY_STRING'];
	}
	header('Location: ' . $destino_uri, true, 301);
	exit;
}

@session_start();
require_once("verificar.php");
require_once("../conexao.php");

// O painel acompanha o segmento configurado no sistema (barbearia ou salao)
$sufixo_tema_painel = (isset($segmento) && $segmento == 'salao') ? '-salao' : '';

// Conteudo proprio do salao (nome, imagens e textos usados no tema salao)
$salao_cfg = bb_dados_salao($pdo);
if (!$salao_cfg) {
	$salao_cfg = array('nome' => '', 'img_banner_index' => '', 'imagem_sobre' => '', 'texto_sobre' => '', 'texto_rodape' => '', 'texto_agendamento' => '', 'email' => '');
}

$pag_inicial = 'home';

$id_usuario = $_SESSION['id'];

$query = $pdo->prepare("SELECT * from usuarios where id = :id");
$query->execute(array(':id' => $id_usuario));
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$nome_usuario = $res[0]['nome'];
	$email_usuario = $res[0]['email'];
	$cpf_usuario = $res[0]['cpf'];
	$senha_usuario = $res[0]['senha'];
	$nivel_usuario = $res[0]['nivel'];
	$telefone_usuario = $res[0]['telefone'];
	$endereco_usuario = $res[0]['endereco'];
	$foto_usuario = $res[0]['foto'];
	$atendimento = $res[0]['atendimento'];
}

if(@$_SESSION['nivel'] != 'Administrador'){
	require_once("verificar-permissoes.php");
}

if(@$_GET['pag'] == ""){
	$pag = $pag_inicial;
}else{
	$pag = $_GET['pag'];
}


$data_atual = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_mes = $ano_atual."-".$mes_atual."-01";
$data_ano = $ano_atual."-01-01";


$partesInicial = explode('-', $data_atual);
$dataDiaInicial = $partesInicial[2];
$dataMesInicial = $partesInicial[1];

?>

<!DOCTYPE HTML>
<html>
<head>
	<title><?php echo $nome_sistema ?></title>
	<link rel="icon" type="image/png" href="../../images/<?php echo $icone_site ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="keywords" content="" />
	<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>

	<!-- Google Fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

	<!-- Bootstrap Core CSS -->
	<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />

	<!-- Custom CSS - Novo Design (segue o segmento configurado) -->
	<link href="css/style-novo<?php echo $sufixo_tema_painel ?>.css" rel='stylesheet' type='text/css' />
	<!-- CSS Antigo como fallback -->
	<link href="css/style.css" rel='stylesheet' type='text/css' />
	<!-- CSS Override para garantir aplicação dos estilos -->
	<link href="css/override<?php echo $sufixo_tema_painel ?>.css" rel='stylesheet' type='text/css' />

	<!-- font-awesome icons CSS -->
	<link href="css/font-awesome.css" rel="stylesheet"> 
	<!-- //font-awesome icons CSS-->

	<!-- side nav css file -->
	<link href='css/SidebarNav.min.css' media='all' rel='stylesheet' type='text/css'/>
	<!-- //side nav css file -->

	<link rel="stylesheet" href="css/monthly.css">

	<!-- js-->
	<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/modernizr.custom.js"></script>

	<!--webfonts-->
	<link href="//fonts.googleapis.com/css?family=PT+Sans:400,400i,700,700i&amp;subset=cyrillic,cyrillic-ext,latin-ext" rel="stylesheet">
	<!--//webfonts--> 

	<!-- chart -->
	<script src="js/Chart.js"></script>
	<!-- //chart -->

	<!-- Metis Menu -->
	<script src="js/metisMenu.min.js"></script>
	<script src="js/custom.js"></script>
	<link href="css/custom.css" rel="stylesheet">
	<!--//Metis Menu -->
	<style>
		#chartdiv {
			width: 100%;
			height: 295px;
		}
	</style>
	<!--pie-chart --><!-- index page sales reviews visitors pie chart -->
	<script src="js/pie-chart.js" type="text/javascript"></script>
	<script type="text/javascript">

		$(document).ready(function () {
			$('#demo-pie-1').pieChart({
				barColor: '#2dde98',
				trackColor: '#eee',
				lineCap: 'round',
				lineWidth: 8,
				onStep: function (from, to, percent) {
					$(this.element).find('.pie-value').text(Math.round(percent) + '%');
				}
			});

			$('#demo-pie-2').pieChart({
				barColor: '#8e43e7',
				trackColor: '#eee',
				lineCap: 'butt',
				lineWidth: 8,
				onStep: function (from, to, percent) {
					$(this.element).find('.pie-value').text(Math.round(percent) + '%');
				}
			});

			$('#demo-pie-3').pieChart({
				barColor: '#e32424',
				trackColor: '#eee',
				lineCap: 'square',
				lineWidth: 8,
				onStep: function (from, to, percent) {
					$(this.element).find('.pie-value').text(Math.round(percent) + '%');
				}
			});


		});

	</script>
	<!-- //pie-chart --><!-- index page sales reviews visitors pie chart -->


	<link rel="stylesheet" type="text/css" href="DataTables/datatables.min.css"/>
 	<script type="text/javascript" src="DataTables/datatables.min.js"></script>

	<!-- CSS responsivo do painel -->
	<link href="css/responsivo.css" rel="stylesheet" type="text/css" />
	
</head> 
<body class="cbp-spmenu-push">

	<div class="main-content">
<?php require_once(__DIR__ . '/quota-banner.php'); ?>
		<!-- Barra superior apenas no celular/tablet -->
		<div class="barra-mobile">
			<button type="button" class="botao-menu" id="btn-menu-mobile" aria-label="Abrir menu" aria-expanded="false">
				<i class="fa fa-bars"></i>
			</button>
			<div class="titulo-barra">
				<?php echo htmlspecialchars($nome_sistema) ?>
				<small>Painel administrativo</small>
			</div>
		</div>
		<div class="fundo-menu" id="fundo-menu"></div>
		<div class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-left" id="cbp-spmenu-s1">
			<!--left-fixed -navigation-->
			<aside class="sidebar-left" style="height:100%;">
				<nav class="navbar navbar-inverse" >
					<div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".collapse" aria-expanded="false">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<h1><a class="navbar-brand" href="index.php"><span class="fa fa-cogs"></span> Painel<span class="dashboard_text"><?php echo $nome_sistema ?></span></a></h1>
					</div>
					<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
						<ul class="sidebar-menu">
							<li class="header">MENU DE NAVEGAÇÃO</li>


							<li class="treeview <?php echo @$home ?>">
								<a href="index.php">
									<i class="fa fa-dashboard"></i> <span>Home</span>
								</a>
							</li>


							<li class="treeview <?php echo @$menu_pessoas ?>">
								<a href="#">
									<i class="fa fa-users"></i>
									<span>Pessoas</span>
									<i class="fa fa-angle-left pull-right"></i>
								</a>
								<ul class="treeview-menu">
									<li class="<?php echo @$usuarios ?>"><a href="index.php?pag=usuarios"><i class="fa fa-angle-right"></i>Usuários</a></li>
									<li class="<?php echo @$funcionarios ?>"><a href="index.php?pag=funcionarios"><i class="fa fa-angle-right"></i>Funcionários</a></li>
									<li class="<?php echo @$clientes ?>"><a href="index.php?pag=clientes"><i class="fa fa-angle-right"></i>Clientes</a></li>

									<li class="<?php echo @$clientes_retorno ?>"><a href="index.php?pag=clientes_retorno"><i class="fa fa-angle-right"></i>Clientes Retornos</a></li>

									<li class="<?php echo @$fornecedores ?>"><a href="index.php?pag=fornecedores"><i class="fa fa-angle-right"></i>Fornecedores</a></li>

								</ul>
							</li>



							<li class="treeview <?php echo @$menu_cadastros ?>" >
								<a href="#">
									<i class="fa fa-plus"></i>
									<span>Cadastros</span>
									<i class="fa fa-angle-left pull-right"></i>
								</a>
								<ul class="treeview-menu">
									<li class="<?php echo @$servicos ?>"><a href="index.php?pag=servicos"><i class="fa fa-angle-right"></i>Serviços</a></li>

									<li class="<?php echo @$cargos ?>"><a href="index.php?pag=cargos"><i class="fa fa-angle-right"></i>Cargos</a></li>

									<li class="<?php echo @$cat_servicos ?>"><a href="index.php?pag=cat_servicos"><i class="fa fa-angle-right"></i>Categoria Serviços</a></li>

									<li class="<?php echo @$grupos ?>"><a href="index.php?pag=grupos"><i class="fa fa-angle-right"></i>Grupo Acessos</a></li>

									<li class="<?php echo @$acessos ?>"><a href="index.php?pag=acessos"><i class="fa fa-angle-right"></i>Acessos</a></li>
								
								</ul>
							</li>


							<li class="treeview <?php echo @$menu_produtos ?>">
								<a href="#">
									<i class="fa fa-barcode"></i>
									<span>Produtos</span>
									<i class="fa fa-angle-left pull-right"></i>
								</a>
								<ul class="treeview-menu">

									<li class="<?php echo @$produtos ?>"><a href="index.php?pag=produtos"><i class="fa fa-angle-right"></i>Produtos</a></li>

									<li class="<?php echo @$cat_produtos ?>"><a href="index.php?pag=cat_produtos"><i class="fa fa-angle-right"></i>Categorias</a></li>
									
									<li class="<?php echo @$estoque ?>"><a href="index.php?pag=estoque"><i class="fa fa-angle-right"></i>Estoque Baixo</a></li>

									<li class="<?php echo @$saidas ?>"><a href="index.php?pag=saidas"><i class="fa fa-angle-right"></i>Saídas</a></li>

									<li class="<?php echo @$entradas ?>"><a href="index.php?pag=entradas"><i class="fa fa-angle-right"></i>Entradas</a></li>
								
								</ul>
							</li>



							<li class="treeview <?php echo @$menu_financeiro ?>" >
								<a href="#">
									<i class="fa fa-usd"></i>
									<span>Financeiro</span>
									<i class="fa fa-angle-left pull-right"></i>
								</a>
								<ul class="treeview-menu">

									<li class="<?php echo @$vendas ?>"><a href="index.php?pag=vendas"><i class="fa fa-angle-right"></i>Vendas</a></li>

									<li class="<?php echo @$compras ?>"><a href="index.php?pag=compras"><i class="fa fa-angle-right"></i>Compras</a></li>
									
									<li class="<?php echo @$pagar ?>"><a href="index.php?pag=pagar"><i class="fa fa-angle-right"></i>Contas à Pagar</a></li>

									<li class="<?php echo @$receber ?>"><a href="index.php?pag=receber"><i class="fa fa-angle-right"></i>Contas à Receber</a></li>	

									<li class="<?php echo @$comissoes ?>"><a href="index.php?pag=comissoes"><i class="fa fa-angle-right"></i>Comissões</a></li>									
								
								</ul>
							</li>



							<li class="treeview <?php echo @$menu_agendamentos ?>">
								<a href="#">
									<i class="fa fa-calendar-o"></i>
									<span>Agendamento</span>
									<i class="fa fa-angle-left pull-right"></i>
								</a>
								<ul class="treeview-menu">

									<li class="<?php echo @$agendamentos ?>"><a href="index.php?pag=agendamentos"><i class="fa fa-angle-right"></i>Agendamentos</a></li>

									<li class="<?php echo @$servicos_agenda ?>"><a href="index.php?pag=servicos_agenda"><i class="fa fa-angle-right"></i>Serviços</a></li>
									
																	
								
								</ul>
							</li>



							<li class="treeview <?php echo @$menu_relatorio ?>" >
								<a href="#">
									<i class="fa fa-file-pdf-o"></i>
									<span>Relatórios</span>
									<i class="fa fa-angle-left pull-right"></i>
								</a>
								<ul class="treeview-menu">

									<li class="<?php echo @$rel_produtos ?>"><a href="rel/rel_produtos_class.php" target="_blank"><i class="fa fa-angle-right"></i>Relatório de Produtos</a></li>

									<li class="<?php echo @$rel_entradas ?>"><a href="#" data-toggle="modal" data-target="#RelEntradas"><i class="fa fa-angle-right"></i>Entradas / Ganhos</a></li>

									<li class="<?php echo @$rel_saidas ?>"><a href="#" data-toggle="modal" data-target="#RelSaidas"><i class="fa fa-angle-right"></i>Saídas / Despesas</a></li>

									<li class="<?php echo @$rel_comissoes ?>"><a href="#" data-toggle="modal" data-target="#RelComissoes"><i class="fa fa-angle-right"></i>Relatório de Comissões</a></li>

									<li class="<?php echo @$rel_contas ?>"><a href="#" data-toggle="modal" data-target="#RelCon"><i class="fa fa-angle-right"></i>Relatório de Contas</a></li>


									<li class="<?php echo @$rel_aniv ?>"><a href="#" data-toggle="modal" data-target="#RelAniv"><i class="fa fa-angle-right"></i>Relatório de Aniversáriantes</a></li>


									<li class="<?php echo @$rel_lucro ?>"><a href="#" data-toggle="modal" data-target="#RelLucro"><i class="fa fa-angle-right"></i>Demonstrativo de Lucro</a></li>

														
								
								</ul>
							</li>





							<li class="treeview <?php echo @$menu_site ?>" >
								<a href="#">
									<i class="fa fa-globe"></i>
									<span>Dados do Site</span>
									<i class="fa fa-angle-left pull-right"></i>
								</a>
								<ul class="treeview-menu">

									<li class="<?php echo @$textos_index ?>"><a href="index.php?pag=textos_index"><i class="fa fa-angle-right"></i>Textos Hero</a></li>


									<li class="<?php echo @$comentarios ?>"><a href="index.php?pag=comentarios"><i class="fa fa-angle-right"></i>Depoimentos</a></li>

														
								
								</ul>
							</li>





							<?php if(@$atendimento == 'Sim'){ ?>
							<li class="treeview">
								<a href="index.php?pag=agenda">
									<i class="fa fa-calendar-o"></i> <span>Minha Agenda</span>
								</a>
							</li>
							
							<li class="treeview">
								<a href="index.php?pag=meus_servicos">
									<i class="fa fa-server"></i> <span>Meus Serviços</span>
								</a>
							</li>	


							<li class="treeview">
								<a href="index.php?pag=minhas_comissoes">
									<i class="fa fa-server"></i> <span>Minhas Comissões</span>
								</a>
							</li>						


							<li class="treeview">
								<a href="#">
									<i class="fa fa-usd"></i>
									<span>Horário / Dias</span>
									<i class="fa fa-clock-o pull-right"></i>
								</a>
								<ul class="treeview-menu">

									<li><a href="index.php?pag=horarios"><i class="fa fa-angle-right"></i>Meus Horários</a></li>
									
									<li><a href="index.php?pag=dias"><i class="fa fa-angle-right"></i>Dias Semana</a></li>


																		
								
								</ul>
							</li>

							<?php } ?>



							<!-- Menu do Usuário -->
							<li class="treeview" style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 20px; padding-top: 15px;">
								<a href="#">
									<img src="img/perfil/<?php echo $foto_usuario ?>" alt="" width="25" height="25" style="border-radius: 50%; margin-right: 10px;"> 
									<span><?php echo $nome_usuario ?></span>
									<i class="fa fa-angle-left pull-right"></i>
								</a>
								<ul class="treeview-menu">
									<?php if(@$configuracoes == ''){ ?>
									<li><a href="#" data-toggle="modal" data-target="#modalConfig"><i class="fa fa-cog"></i> Configurações</a></li>
									<?php } ?>
									<li><a href="#" data-toggle="modal" data-target="#modalPerfil"><i class="fa fa-user"></i> Editar Perfil</a></li>
									<li><a href="logout.php" onclick="return confirm('Deseja realmente sair do sistema?')"><i class="fa fa-sign-out"></i> Sair</a></li>
								</ul>
							</li>


						</ul>
					</div>
					<!-- /.navbar-collapse -->
				</nav>
			</aside>
		</div>
		<!--left-fixed -navigation-->








		<!-- main content start-->
		<div id="page-wrapper">
			<?php if (bb_usuario_demo()) { ?>
			<div role="status" style="margin:0 0 18px;padding:12px 16px;border-radius:8px;background:#fff8e1;border:1px solid #f0c36d;color:#7a5b00;font-size:14px;line-height:1.5;">
				<i class="fa fa-eye"></i> <strong>Modo demonstração</strong> — você está vendo o painel como Administrador. As alterações (salvar, excluir, receber, assinar plano) estão desabilitadas nesta conta.
				<a href="../../assinar.php" style="color:#7a5b00;font-weight:600;text-decoration:underline;white-space:nowrap">Criar a conta do meu estabelecimento</a>
			</div>
			<?php } ?>
			<?php require_once("paginas/".$pag.'.php') ?>
		</div>

		<!--footer-->
		<div class="footer">
					
		</div>
		<!--//footer-->
	</div>


	<!-- Classie --><!-- for toggle left push menu script -->
	<script>
		(function () {
			var menu = document.getElementById("cbp-spmenu-s1");
			var botao = document.getElementById("btn-menu-mobile");
			var fundo = document.getElementById("fundo-menu");

			function abrirMenu(abrir) {
				if (!menu) { return; }
				document.body.classList.toggle("menu-aberto", abrir);
				menu.classList.toggle("cbp-spmenu-open", abrir);
				if (botao) {
					botao.setAttribute("aria-expanded", abrir ? "true" : "false");
					botao.innerHTML = abrir ? '<i class="fa fa-times"></i>' : '<i class="fa fa-bars"></i>';
				}
				if (abrir) {
					document.body.classList.remove("cbp-spmenu-push-toright");
				}
			}

			if (botao) {
				botao.addEventListener("click", function () {
					abrirMenu(!document.body.classList.contains("menu-aberto"));
				});
			}

			if (fundo) {
				fundo.addEventListener("click", function () { abrirMenu(false); });
			}

			// Fecha o menu ao escolher uma pagina ou ao voltar para o desktop
			if (menu) {
				menu.addEventListener("click", function (e) {
					var link = e.target.closest ? e.target.closest("a") : null;
					// "#" abre submenu ou modal: nesse caso a gaveta continua aberta.
					if (link && window.innerWidth <= 992 && link.getAttribute("href") !== "#") { abrirMenu(false); }
				});
			}

			window.addEventListener("resize", function () {
				if (window.innerWidth > 992) { abrirMenu(false); }
			});
			document.addEventListener("keydown", function (e) {
				if (e.key === "Escape") { abrirMenu(false); }
			});
		})();
	</script>
	<!-- //Classie --><!-- //for toggle left push menu script -->


	<!--scrolling js-->
	<script src="js/jquery.nicescroll.js"></script>
	<script src="js/scripts.js"></script>
	<!--//scrolling js-->
	
	<!-- side nav js: abrir/fechar os submenus do menu lateral -->
	<script src="js/sidebar-nav.js"></script>
	<!-- //side nav js -->
	
	
	
	<!-- Bootstrap Core JavaScript -->
	<script src="js/bootstrap.js"> </script>
	<!-- //Bootstrap Core JavaScript -->

	<script>
		// Sessao expirada / token CSRF vencido: as acoes do painel respondem
		// 401 (header X-BB-Login) e o usuario volta para a tela de login,
		// em vez de ver o erro no formulario.
		$(document).ajaxComplete(function (evento, xhr) {
			if (xhr && (xhr.status === 401 || xhr.getResponseHeader('X-BB-Login') === '1')) {
				window.location = <?php echo json_encode($url_sistema . 'sistema/?sessao=expirada'); ?>;
			}
		});
	</script>

</body>
</html>





<!-- Mascaras JS -->
<script type="text/javascript" src="js/mascaras.js"></script>

<!-- Ajax para funcionar Mascaras JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script> 




<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style type="text/css">
		.select2-selection__rendered {
			line-height: 36px !important;
			font-size:16px !important;
			color:#666666 !important;

		}

		.select2-selection {
			height: 36px !important;
			font-size:16px !important;
			color:#666666 !important;

		}
	</style>  


<!-- Modal Perfil-->
<div class="modal fade" id="modalPerfil" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel">Editar Perfil</h4>
				<button id="btn-fechar-perfil" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true" >&times;</span>
				</button>
			</div>
			<form method="post" id="form-perfil">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="exampleInputEmail1">Nome</label>
								<input type="text" class="form-control" id="nome-perfil" name="nome" placeholder="Nome" value="<?php echo $nome_usuario ?>" required>    
							</div> 	
						</div>
						<div class="col-md-6">

							<div class="form-group">
								<label for="exampleInputEmail1">Email</label>
								<input type="email" class="form-control" id="email-perfil" name="email" placeholder="Email" value="<?php echo $email_usuario ?>" required>    
							</div> 	
						</div>
					</div>


					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="exampleInputEmail1">Telefone</label>
								<input type="text" class="form-control" id="telefone-perfil" name="telefone" placeholder="Telefone" value="<?php echo $telefone_usuario ?>" >    
							</div> 	
						</div>
						<div class="col-md-6">
							
							<div class="form-group">
								<label for="exampleInputEmail1">CPF</label>
								<input type="text" class="form-control" id="cpf-perfil" name="cpf" placeholder="CPF" value="<?php echo $cpf_usuario ?>">    
							</div> 	
						</div>
					</div>


					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label for="exampleInputEmail1">Senha</label>
								<input type="password" class="form-control" id="senha-perfil" name="senha" placeholder="Senha" value="<?php echo $senha_usuario ?>" required>    
							</div> 	
						</div>
						<div class="col-md-4">
							
							<div class="form-group">
								<label for="exampleInputEmail1">Confirmar Senha</label>
								<input type="password" class="form-control" id="conf-senha-perfil" name="conf_senha" placeholder="Confirmar Senha" required>    
							</div> 	
						</div>

						<div class="col-md-4">
							<div class="form-group">
								<label for="exampleInputEmail1">Atendimento</label>
								<select class="form-control" name="atendimento" id="atendimento-perfil">
									<option <?php if($atendimento == 'Sim'){ ?> selected <?php } ?> value="Sim">Sim</option>
									<option <?php if($atendimento == 'Não'){ ?> selected <?php } ?> value="Não">Não</option>
								</select>  
							</div> 	
						</div>

					</div>


					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="exampleInputEmail1">Endereço</label>
								<input type="text" class="form-control" id="endereco-perfil" name="endereco" placeholder="Rua X Número 1 Bairro xxx" value="<?php echo $endereco_usuario ?>" >    
							</div> 	
						</div>
						
					</div>





						<div class="row">
							<div class="col-md-8">						
								<div class="form-group"> 
									<label>Foto</label> 
									<input class="form-control" type="file" name="foto" onChange="carregarImgPerfil();" id="foto-usu">
								</div>						
							</div>
							<div class="col-md-4">
								<div id="divImg">
									<img src="img/perfil/<?php echo $foto_usuario ?>"  width="80px" id="target-usu">									
								</div>
							</div>

						</div>


					
						<input type="hidden" name="id" value="<?php echo $id_usuario ?>">

					<br>
					<small><div id="mensagem-perfil" align="center"></div></small>
				</div>
				<div class="modal-footer">      
					<button type="submit" class="btn btn-primary">Editar Perfil</button>
				</div>
			</form>
		</div>
	</div>
</div>









<!-- Modal Config-->
<div class="modal fade" id="modalConfig" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel">Editar Configurações</h4>
				<button id="btn-fechar-config" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
					<span aria-hidden="true" >&times;</span>
				</button>
			</div>
			<form method="post" id="form-config">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label for="exampleInputEmail1">Nome do Estabelecimento</label>
								<input type="text" class="form-control" id="nome_sistema" name="nome_sistema" placeholder="Nome do Estabelecimento" value="<?php echo $nome_sistema_base ?>" required>    
							</div> 	
						</div>
						<div class="col-md-4">

							<div class="form-group">
								<label for="exampleInputEmail1">Email do Estabelecimento</label>
								<input type="email" class="form-control" id="email_sistema" name="email_sistema" placeholder="Email" value="<?php echo $email_sistema ?>" required>    
							</div> 	
						</div>

						<div class="col-md-4">

							<div class="form-group">
								<label for="exampleInputEmail1">Whatsapp do Estabelecimento</label>
								<input type="text" class="form-control" id="whatsapp_sistema" name="whatsapp_sistema" placeholder="Whatsapp" value="<?php echo $whatsapp_sistema ?>" required>    
							</div> 	
						</div>
					</div>


					<div class="row">
						
						<div class="col-md-3">

							<div class="form-group">
								<label for="exampleInputEmail1">Tel Fixo do Estabelecimento</label>
								<input type="text" class="form-control" id="telefone_fixo_sistema" name="telefone_fixo_sistema" placeholder="Fixo" value="<?php echo $telefone_fixo_sistema ?>" required>    
							</div> 	
						</div>
						<div class="col-md-7">
							
							<div class="form-group">
								<label for="exampleInputEmail1">Endereço do Estabelecimento</label>
								<input type="text" class="form-control" id="endereco_sistema" name="endereco_sistema" placeholder="Rua X Numero X Bairro Cidade" value="<?php echo $endereco_sistema ?>">    
							</div> 	
						</div>

						<div class="col-md-2">
							<div class="form-group">
								<label for="exampleInputEmail1">Tipo Relatório</label>
								<select class="form-control" name="tipo_rel" id="tipo_rel">
									<option value="PDF" <?php if($tipo_rel == 'PDF'){?> selected <?php } ?> >PDF</option>
									<option value="HTML" <?php if($tipo_rel == 'HTML'){?> selected <?php } ?> >HTML</option>
								</select>   
							</div> 	
						</div>
					</div>


					<div class="row">
						


						<div class="col-md-2">
							<div class="form-group">
								<label for="exampleInputEmail1">Tipo Comissão</label>
								<select class="form-control" name="tipo_comissao" id="tipo_comissao">
									<option value="Porcentagem" <?php if($tipo_comissao == 'Porcentagem'){?> selected <?php } ?> >Porcentagem</option>
									<option value="R$" <?php if($tipo_comissao == 'R$'){?> selected <?php } ?> >R$ Reais</option>
								</select>   
							</div> 	
						</div>


						<div class="col-md-4">
							<div class="form-group">
								<label for="exampleInputEmail1">Segmento do Negócio</label>
								<select class="form-control" name="segmento" id="segmento">
									<option value="barbearia" <?php if($segmento != 'salao'){?> selected <?php } ?> >Barbearia</option>
									<option value="salao" <?php if($segmento == 'salao'){?> selected <?php } ?> >Salão de Beleza</option>
								</select>
								<small style="color:#888">Aplica o tema em todo o sistema: site, agendamento, login e painel</small>
							</div>
						</div>

						<div class="col-md-4">
							<div class="form-group">
								<label for="exampleInputEmail1">Instagram</label>
								<input type="text" class="form-control" id="instagram_sistema" name="instagram_sistema" placeholder="Link do Perfil no Instagram" value="<?php echo $instagram_sistema ?>">   
							</div> 	
						</div>

					<div class="col-md-3">
						<div class="form-group">
								<label for="exampleInputEmail1">Mapa Site <small>(Url incorporada)</small></label>
								<input type="text" class="form-control" id="mapa" name="mapa" placeholder="" value='<?php echo $mapa ?>'>  
							</div> 	
						</div>

						


						
					</div>

					<div class="row">
						<div class="col-md-12">
						<div class="form-group">
								<label for="exampleInputEmail1">Texto Rodapé Site <small>(255) Caracteres</small></label>
								<input maxlength="255" type="text" class="form-control" id="texto_rodape" name="texto_rodape" placeholder="Texto para o Rodapé do site" value="<?php echo $texto_rodape_base ?>">   
							</div> 
						</div>
					</div>


					<div class="row">
						<div class="col-md-12">
						<div class="form-group">
								<label for="exampleInputEmail1">Texto Sobre (Site) <small>(600) Caracteres</small></label>
								<input maxlength="255" type="text" class="form-control" id="texto_sobre" name="texto_sobre" placeholder="Texto para a área Sobre a empresa no site" value="<?php echo $texto_sobre_base ?>">   
							</div> 
						</div>
					</div>


					<div class="row">
						<div class="col-md-9">
						<div class="form-group">
								<label for="exampleInputEmail1">Texto Cartão Fidelidade</label>
								<input maxlength="255" type="text" class="form-control" id="texto_fidelidade" name="texto_fidelidade" placeholder="Parabéns, você completou seus cartões, você ganhou ..." value="<?php echo @$texto_fidelidade ?>">   
							</div> 
						</div>

						<div class="col-md-3">
						<div class="form-group">
								<label for="exampleInputEmail1">Cartões Troca</label>
								<input type="number" class="form-control" id="quantidade_cartoes" name="quantidade_cartoes" placeholder="Quantidade Cartões Troca" value="<?php echo $quantidade_cartoes ?>">   
							</div> 
						</div>
					</div>


					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label for="exampleInputEmail1">Texto Agendamento</label>
								<input maxlength="30" type="text" class="form-control" id="texto_agendamento" name="texto_agendamento" placeholder="Selecionar Cabelereira" value="<?php echo $texto_agendamento_base ?>">   
							</div> 
						</div>

						<div class="col-md-4">
							<div class="form-group">
								<label for="exampleInputEmail1">Mensagem Agendamento</label>
								<select class="form-control" name="msg_agendamento" id="msg_agendamento">
									<option value="Sim" <?php if($msg_agendamento == 'Sim'){?> selected <?php } ?> >Sim</option>
									<option value="Não" <?php if($msg_agendamento == 'Não'){?> selected <?php } ?> >Não</option>
								</select>      
							</div> 
						</div>

						</div>
					



				

						<div class="row">

							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Logo (*PNG)</label> 
									<input class="form-control" type="file" name="foto-logo" onChange="carregarImgLogo();" id="foto-logo">
								</div>						
							</div>
							<div class="col-md-2">
								<div id="divImg">
									<img src="../img/<?php echo $logo_sistema ?>"  width="80px" id="target-logo">									
								</div>
							</div>


							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Ícone (*Png)</label> 
									<input class="form-control" type="file" name="foto-icone" onChange="carregarImgIcone();" id="foto-icone">
								</div>						
							</div>
							<div class="col-md-2">
								<div id="divImg">
									<img src="../img/<?php echo $icone_sistema ?>"  width="50px" id="target-icone">									
								</div>
							</div>

						</div>



						<div class="row">

							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Logo Relatório (*Jpg)</label> 
									<input class="form-control" type="file" name="foto-logo-rel" onChange="carregarImgLogoRel();" id="foto-logo-rel">
								</div>						
							</div>
							<div class="col-md-2">
								<div id="divImg">
									<img src="../img/<?php echo $logo_rel ?>"  width="80px" id="target-logo-rel">									
								</div>
							</div>



							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Ícone Site (*png)</label> 
									<input class="form-control" type="file" name="foto-icone-site" onChange="carregarImgIconeSite();" id="foto-icone-site">
								</div>						
							</div>
							<div class="col-md-2">
								<div id="divImg">
									<img src="../../images/<?php echo $icone_site ?>"  width="50px" id="target-icone-site">									
								</div>
							</div>



						</div>



						<div class="row">

							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Imagem Área Sobre (Site)</label> 
									<input class="form-control" type="file" name="foto-sobre" onChange="carregarImgSobre();" id="foto-sobre">
								</div>						
							</div>
							<div class="col-md-2">
								<div id="divImg">
									<img src="../../images/<?php echo $imagem_sobre_base ?>"  width="80px" id="target-sobre">									
								</div>
							</div>



							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Imagem Banner Index <small>(1500x1000)</small></label> 
									<input class="form-control" type="file" name="foto-banner-index" onChange="carregarImgBannerIndex();" id="foto-banner-index">
								</div>						
							</div>
							<div class="col-md-2">
								<div id="divImg">
									<img src="../../images/<?php echo $img_banner_index_base ?>"  width="80px" id="target-banner-index">									
								</div>
							</div>



						</div>
					
						

					<br>
					<small><div id="mensagem-config" align="center"></div></small>
				</div>
				<div class="modal-footer">      
					<div class="row">
						<div class="col-md-12">
							<hr>
							<h4>Conteudo do Salao de Beleza</h4>
							<small>Usado no site, no login e no painel quando o Segmento do Negocio for "Salao de Beleza".</small>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="nome_salao">Nome do Salao</label>
								<input maxlength="50" type="text" class="form-control" id="nome_salao" name="nome_salao" placeholder="Ex.: Salao Lumiere" value="<?php echo @$salao_cfg['nome'] ?>">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label for="texto_agendamento_salao">Texto Agendamento (Salao)</label>
								<input maxlength="30" type="text" class="form-control" id="texto_agendamento_salao" name="texto_agendamento_salao" placeholder="Ex.: Escolha seu Profissional" value="<?php echo @$salao_cfg['texto_agendamento'] ?>">
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="texto_sobre_salao">Texto Sobre (Salao) <small>(600) Caracteres</small></label>
								<input maxlength="600" type="text" class="form-control" id="texto_sobre_salao" name="texto_sobre_salao" placeholder="Texto da area Sobre quando o tema for Salao" value="<?php echo @$salao_cfg['texto_sobre'] ?>">
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="texto_rodape_salao">Texto Rodape (Salao) <small>(255) Caracteres</small></label>
								<input maxlength="255" type="text" class="form-control" id="texto_rodape_salao" name="texto_rodape_salao" placeholder="Texto do rodape quando o tema for Salao" value="<?php echo @$salao_cfg['texto_rodape'] ?>">
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="whatsapp_salao">Whatsapp (Salao)</label>
								<input maxlength="20" type="text" class="form-control" id="whatsapp_salao" name="whatsapp_salao" placeholder="(11) 98888-7777" value="<?php echo @$salao_cfg['whatsapp'] ?>">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="email_salao">Email (Salao)</label>
								<input maxlength="50" type="email" class="form-control" id="email_salao" name="email_salao" placeholder="contato@seusalao.com" value="<?php echo @$salao_cfg['email'] ?>">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="instagram_salao">Instagram (Salao)</label>
								<input maxlength="100" type="text" class="form-control" id="instagram_salao" name="instagram_salao" placeholder="https://www.instagram.com/seu-salao/" value="<?php echo @$salao_cfg['instagram'] ?>">
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-8">
							<div class="form-group">
								<label for="endereco_salao">Endereco (Salao)</label>
								<input maxlength="100" type="text" class="form-control" id="endereco_salao" name="endereco_salao" placeholder="Rua, numero - bairro - cidade/UF" value="<?php echo @$salao_cfg['endereco'] ?>">
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label for="texto_fidelidade_salao">Texto Cartao Fidelidade (Salao)</label>
								<input maxlength="255" type="text" class="form-control" id="texto_fidelidade_salao" name="texto_fidelidade_salao" value="<?php echo @$salao_cfg['texto_fidelidade'] ?>">
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>Imagem Area Sobre (Salao)</label>
								<input class="form-control" type="file" name="foto-sobre-salao">
							</div>
						</div>
						<div class="col-md-2">
							<img src="../../images/<?php echo @$salao_cfg['imagem_sobre'] ?>" width="80px">
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label>Imagem Banner Index (Salao)</label>
								<input class="form-control" type="file" name="foto-banner-salao">
							</div>
						</div>
						<div class="col-md-2">
							<img src="../../images/<?php echo @$salao_cfg['img_banner_index'] ?>" width="80px">
						</div>
					</div>

					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>Logo (Salao) <small>PNG</small></label>
								<input class="form-control" type="file" name="foto-logo-salao">
							</div>
						</div>
						<div class="col-md-2">
							<img src="../img/<?php echo @$salao_cfg['logo'] ?>" width="70px">
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label>Icone do Site (Salao) <small>PNG</small></label>
								<input class="form-control" type="file" name="foto-icone-salao">
							</div>
						</div>
						<div class="col-md-2">
							<img src="../../images/<?php echo @$salao_cfg['icone_site'] ?>" width="40px">
						</div>
					</div>

					<button type="submit" class="btn btn-primary">Salvar Dados</button>
				</div>
			</form>
		</div>
	</div>
</div>











	<!-- Modal Rel Entradas / Ganhos -->
	<div class="modal fade" id="RelEntradas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="exampleModalLabel">Relatório de Ganhos
						<small>(
							<a href="#" onclick="datas('1980-01-01', 'tudo-Ent', 'Ent')">
								<span style="color:#000" id="tudo-Ent">Tudo</span>
							</a> / 
							<a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Ent', 'Ent')">
								<span id="hoje-Ent">Hoje</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Ent', 'Ent')">
								<span style="color:#000" id="mes-Ent">Mês</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Ent', 'Ent')">
								<span style="color:#000" id="ano-Ent">Ano</span>
							</a> 
						)</small>



					</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form method="post" action="rel/rel_entradas_class.php" target="_blank">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Data Inicial</label> 
									<input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Ent" value="<?php echo date('Y-m-d') ?>" required> 
								</div>						
							</div>
							<div class="col-md-4">
								<div class="form-group"> 
									<label>Data Final</label> 
									<input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Ent" value="<?php echo date('Y-m-d') ?>" required> 
								</div>
							</div>

							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Entradas / Ganhos</label> 
									<select class="form-control sel13" name="filtro" style="width:100%;">
										<option value="">Todas</option>
										<option value="Venda">Vendas</option>
										<option value="Serviço">Serviços</option>
										<option value="Conta">Demais Ganhos</option>
										
									</select> 
								</div>						
							</div>

						</div>


						

					</div>

					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Gerar Relatório</button>
					</div>
				</form>

			</div>
		</div>
	</div>









	<!-- Modal Rel Saidas / Despesas -->
	<div class="modal fade" id="RelSaidas" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="exampleModalLabel">Relatório de Saídas
						<small>(
							<a href="#" onclick="datas('1980-01-01', 'tudo-Saida', 'Saida')">
								<span style="color:#000" id="tudo-Saida">Tudo</span>
							</a> / 
							<a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Saida', 'Saida')">
								<span id="hoje-Saida">Hoje</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Saida', 'Saida')">
								<span style="color:#000" id="mes-Saida">Mês</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Saida', 'Saida')">
								<span style="color:#000" id="ano-Saida">Ano</span>
							</a> 
						)</small>



					</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form method="post" action="rel/rel_saidas_class.php" target="_blank">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Data Inicial</label> 
									<input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Saida" value="<?php echo date('Y-m-d') ?>" required> 
								</div>						
							</div>
							<div class="col-md-4">
								<div class="form-group"> 
									<label>Data Final</label> 
									<input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Saida" value="<?php echo date('Y-m-d') ?>" required> 
								</div>
							</div>

							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Saídas / Despesas</label> 
									<select class="form-control sel13" name="filtro" style="width:100%;">
										<option value="">Todas</option>
										<option value="Conta">Despesas</option>
										<option value="Comissão">Comissões</option>
										<option value="Compra">Compras</option>
										
									</select> 
								</div>						
							</div>

						</div>


						

					</div>

					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Gerar Relatório</button>
					</div>
				</form>

			</div>
		</div>
	</div>










	<!-- Modal Rel Comissoes -->
	<div class="modal fade" id="RelComissoes" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="exampleModalLabel">Relatório de Comissões
						<small>(
							<a href="#" onclick="datas('1980-01-01', 'tudo-Com', 'Com')">
								<span style="color:#000" id="tudo-Com">Tudo</span>
							</a> / 
							<a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Com', 'Com')">
								<span id="hoje-Com">Hoje</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Com', 'Com')">
								<span style="color:#000" id="mes-Com">Mês</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Com', 'Com')">
								<span style="color:#000" id="ano-Com">Ano</span>
							</a> 
						)</small>



					</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form method="post" action="rel/rel_comissoes_class.php" target="_blank">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Data Inicial</label> 
									<input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Com" value="<?php echo date('Y-m-d') ?>" required> 
								</div>						
							</div>
							<div class="col-md-4">
								<div class="form-group"> 
									<label>Data Final</label> 
									<input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Com" value="<?php echo date('Y-m-d') ?>" required> 
								</div>
							</div>

								<div class="col-md-4">						
								<div class="form-group"> 
									<label>Pago</label> 
									<select class="form-control " name="pago" style="width:100%;">
										<option value="">Todas</option>
										<option value="Sim">Somente Pagas</option>
										<option value="Não">Pendentes</option>
										
									</select> 
								</div>						
							</div>

						</div>

						<div class="row">
							<div class="col-md-12">						
								<div class="form-group"> 
									<label>Funcionário</label> 
									<select class="form-control sel15" name="funcionario" style="width:100%;">
										<option value="">Todos</option>
										<?php 
				$query = $pdo->query("SELECT * FROM usuarios where atendimento = 'Sim' ORDER BY id desc");
				$res = $query->fetchAll(PDO::FETCH_ASSOC);
				$total_reg = @count($res);
				if($total_reg > 0){
					for($i=0; $i < $total_reg; $i++){
						foreach ($res[$i] as $key => $value){}
							echo '<option value="'.$res[$i]['id'].'">'.$res[$i]['nome'].'</option>';
					}
				}?>
										
									</select> 
								</div>						
							</div>	
						</div>


						

					</div>

					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Gerar Relatório</button>
					</div>
				</form>

			</div>
		</div>
	</div>








	<!-- Modal Rel Contas -->
	<div class="modal fade" id="RelCon" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="exampleModalLabel">Relatório de Contas
						<small>(
							<a href="#" onclick="datas('1980-01-01', 'tudo-Con', 'Con')">
								<span style="color:#000" id="tudo-Con">Tudo</span>
							</a> / 
							<a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Con', 'Con')">
								<span id="hoje-Con">Hoje</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Con', 'Con')">
								<span style="color:#000" id="mes-Con">Mês</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Con', 'Con')">
								<span style="color:#000" id="ano-Con">Ano</span>
							</a> 
						)</small>



					</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form method="post" action="rel/rel_contas_class.php" target="_blank">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Data Inicial</label> 
									<input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Con" value="<?php echo date('Y-m-d') ?>" required> 
								</div>						
							</div>
							<div class="col-md-4">
								<div class="form-group"> 
									<label>Data Final</label> 
									<input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Con" value="<?php echo date('Y-m-d') ?>" required> 
								</div>
							</div>

							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Pago</label> 
									<select class="form-control" name="pago" style="width:100%;">
										<option value="">Todas</option>
										<option value="Sim">Somente Pagas</option>
										<option value="Não">Pendentes</option>
										
									</select> 
								</div>						
							</div>

						</div>



							<div class="row">
							<div class="col-md-6">						
								<div class="form-group"> 
									<label>Pagar / Receber</label> 
									<select class="form-control sel13" name="tabela" style="width:100%;">
										<option value="pagar">Contas à Pagar</option>
										<option value="receber">Contas à Receber</option>
																				
									</select> 
								</div>						
							</div>
							<div class="col-md-6">
								<div class="form-group"> 
									<label>Consultar Por</label> 
									<select class="form-control sel13" name="busca" style="width:100%;">
										<option value="data_venc">Data de Vencimento</option>
										<option value="data_pgto">Data de Pagamento</option>
																				
									</select>
								</div>
							</div>

							

						</div>


						

					</div>

					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Gerar Relatório</button>
					</div>
				</form>

			</div>
		</div>
	</div>








	<!-- Modal Rel Lucro -->
	<div class="modal fade" id="RelLucro" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="exampleModalLabel">Demonstrativo de Lucro
						<small>(
							<a href="#" onclick="datas('1980-01-01', 'tudo-Lucro', 'Lucro')">
								<span style="color:#000" id="tudo-Lucro">Tudo</span>
							</a> / 
							<a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Lucro', 'Lucro')">
								<span id="hoje-Lucro">Hoje</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Lucro', 'Lucro')">
								<span style="color:#000" id="mes-Lucro">Mês</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Lucro', 'Lucro')">
								<span style="color:#000" id="ano-Lucro">Ano</span>
							</a> 
						)</small>



					</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form method="post" action="rel/rel_lucro_class.php" target="_blank">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Data Inicial</label> 
									<input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Lucro" value="<?php echo date('Y-m-d') ?>" required> 
								</div>						
							</div>
							<div class="col-md-4">
								<div class="form-group"> 
									<label>Data Final</label> 
									<input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Lucro" value="<?php echo date('Y-m-d') ?>" required> 
								</div>
							</div>						

						</div>


						

					</div>

					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Gerar Relatório</button>
					</div>
				</form>

			</div>
		</div>
	</div>










	<!-- Modal Rel Anivesariantes -->
	<div class="modal fade" id="RelAniv" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="exampleModalLabel">Relatório de Aniversáriantes
						<small>(
							<a href="#" onclick="datas('1980-01-01', 'tudo-Aniv', 'Aniv')">
								<span style="color:#000" id="tudo-Aniv">Tudo</span>
							</a> / 
							<a href="#" onclick="datas('<?php echo $data_atual ?>', 'hoje-Aniv', 'Aniv')">
								<span id="hoje-Aniv">Hoje</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_mes ?>', 'mes-Aniv', 'Aniv')">
								<span style="color:#000" id="mes-Aniv">Mês</span>
							</a> /
							<a href="#" onclick="datas('<?php echo $data_ano ?>', 'ano-Aniv', 'Aniv')">
								<span style="color:#000" id="ano-Aniv">Ano</span>
							</a> 
						)</small>



					</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form method="post" action="rel/rel_aniv_class.php" target="_blank">
					<div class="modal-body">

						<div class="row">
							<div class="col-md-4">						
								<div class="form-group"> 
									<label>Data Inicial</label> 
									<input type="date" class="form-control" name="dataInicial" id="dataInicialRel-Aniv" value="<?php echo date('Y-m-d') ?>" required> 
								</div>						
							</div>
							<div class="col-md-4">
								<div class="form-group"> 
									<label>Data Final</label> 
									<input type="date" class="form-control" name="dataFinal" id="dataFinalRel-Aniv" value="<?php echo date('Y-m-d') ?>" required> 
								</div>
							</div>						

						</div>


						

					</div>

					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Gerar Relatório</button>
					</div>
				</form>

			</div>
		</div>
	</div>





<script type="text/javascript">
	$(document).ready(function() {		
		$('.sel15').select2({	
			dropdownParent: $('#RelComissoes')		
		});
	});
</script>


 <script type="text/javascript">
	$("#form-perfil").submit(function () {

		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: "editar-perfil.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-perfil').text('');
				$('#mensagem-perfil').removeClass()
				if (mensagem.trim() == "Editado com Sucesso") {

					$('#btn-fechar-perfil').click();
					location.reload();			
					
				} else {

					$('#mensagem-perfil').addClass('text-danger')
					$('#mensagem-perfil').text(mensagem)
				}


			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>








<script type="text/javascript">
	function carregarImgPerfil() {
    var target = document.getElementById('target-usu');
    var file = document.querySelector("#foto-usu").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>







 <script type="text/javascript">
	$("#form-config").submit(function () {

		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: "editar-config.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-config').text('');
				$('#mensagem-config').removeClass()
				if (mensagem.trim() == "Editado com Sucesso") {

					$('#btn-fechar-config').click();
					location.reload();			
					
				} else {

					$('#mensagem-config').addClass('text-danger')
					$('#mensagem-config').text(mensagem)
				}


			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>




<script type="text/javascript">
	function carregarImgLogo() {
    var target = document.getElementById('target-logo');
    var file = document.querySelector("#foto-logo").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>





<script type="text/javascript">
	function carregarImgLogoRel() {
    var target = document.getElementById('target-logo-rel');
    var file = document.querySelector("#foto-logo-rel").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>





<script type="text/javascript">
	function carregarImgIcone() {
    var target = document.getElementById('target-icone');
    var file = document.querySelector("#foto-icone").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>





<script type="text/javascript">
	function carregarImgIconeSite() {
    var target = document.getElementById('target-icone-site');
    var file = document.querySelector("#foto-icone-site").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>






<script type="text/javascript">
	function carregarImgBannerIndex() {
    var target = document.getElementById('target-banner-index');
    var file = document.querySelector("#foto-banner-index").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>





<script type="text/javascript">
	function carregarImgSobre() {
    var target = document.getElementById('target-sobre');
    var file = document.querySelector("#foto-sobre").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>




	<script type="text/javascript">
		function datas(data, id, campo){		

			var data_atual = "<?=$data_atual?>";
			var separarData = data_atual.split("-");
			var mes = separarData[1];
			var ano = separarData[0];

			var separarId = id.split("-");

			if(separarId[0] == 'tudo'){
				data_atual = '2100-12-31';
			}

			if(separarId[0] == 'ano'){
				data_atual = ano + '-12-31';
			}

			if(separarId[0] == 'mes'){
				if(mes == 1 || mes == 3 || mes == 5 || mes == 7 || mes == 8 || mes == 10 || mes == 12){
					data_atual = ano + '-'+ mes + '-31';
				}else if (mes == 4 || mes == 6 || mes == 9 || mes == 11){
					data_atual = ano + '-'+ mes + '-30';
				}else{
					data_atual = ano + '-'+ mes + '-28';
				}

			}

			$('#dataInicialRel-'+campo).val(data);
			$('#dataFinalRel-'+campo).val(data_atual);

			document.getElementById('hoje-'+campo).style.color = "#000";
			document.getElementById('mes-'+campo).style.color = "#000";
			document.getElementById(id).style.color = "blue";	
			document.getElementById('tudo-'+campo).style.color = "#000";
			document.getElementById('ano-'+campo).style.color = "#000";
			document.getElementById(id).style.color = "blue";		
		}
	</script>
