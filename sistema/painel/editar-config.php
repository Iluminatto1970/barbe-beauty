<?php 
require_once('../conexao.php');
bb_csrf_check_request();

$nome = $_POST['nome_sistema'];
$email = $_POST['email_sistema'];
$whatsapp = $_POST['whatsapp_sistema'];
$fixo = $_POST['telefone_fixo_sistema'];
$endereco = $_POST['endereco_sistema'];
$tipo_rel = $_POST['tipo_rel'];
$instagram = $_POST['instagram_sistema'];
$tipo_comissao = $_POST['tipo_comissao'];
$texto_rodape = $_POST['texto_rodape'];
$texto_sobre = $_POST['texto_sobre'];
$mapa = $_POST['mapa'];
$quantidade_cartoes = $_POST['quantidade_cartoes'];
$texto_fidelidade = $_POST['texto_fidelidade'];
$texto_agendamento = $_POST['texto_agendamento'];
$msg_agendamento = $_POST['msg_agendamento'];
$segmento = (isset($_POST['segmento']) && $_POST['segmento'] == 'salao') ? 'salao' : 'barbearia';

// Arquivos de marca exclusivos do estabelecimento (nao sobrescrevem outro cliente)
$arquivo_logo       = bb_arquivo_tenant('logo.png');
$arquivo_icone      = bb_arquivo_tenant('favicon.png');
$arquivo_logo_rel   = bb_arquivo_tenant('logo_rel.jpg');
$arquivo_icone_site = bb_arquivo_tenant('favicon.png');

//SCRIPT PARA SUBIR FOTO NO SERVIDOR
if(@$_FILES['foto-logo']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-logo'], array('png'));
	if ($erro_img !== '') { echo $erro_img; exit(); }
	bb_imagem_salvar_upload($_FILES['foto-logo']['tmp_name'], '../img/' . $arquivo_logo, 512);
}

if(@$_FILES['foto-icone']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-icone'], array('png'));
	if ($erro_img !== '') { echo $erro_img; exit(); }
	bb_imagem_salvar_upload($_FILES['foto-icone']['tmp_name'], '../img/' . $arquivo_icone, 512);
}

if(@$_FILES['foto-logo-rel']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-logo-rel'], array('jpg', 'jpeg'));
	if ($erro_img !== '') { echo $erro_img; exit(); }
	bb_imagem_salvar_upload($_FILES['foto-logo-rel']['tmp_name'], '../img/' . $arquivo_logo_rel, 1200);
}

$query = $pdo->query("SELECT * FROM config");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$img_banner_index = $res[0]['img_banner_index'];

//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$nome_img = @$_FILES['foto-banner-index']['name'];
$nome_img = bb_arquivo_tenant(preg_replace('/[ :]+/' , '-' , $nome_img));

if(@$_FILES['foto-banner-index']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-banner-index']);
	if ($erro_img !== '') { echo $erro_img; exit(); }
	$img_banner_index = $nome_img;
	bb_imagem_salvar_upload($_FILES['foto-banner-index']['tmp_name'], '../../images/' . $nome_img, 1920);
}

//validar troca da foto
$query = $pdo->query("SELECT * FROM config");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$img_sobre = $res[0]['imagem_sobre'];

//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$nome_img = @$_FILES['foto-sobre']['name'];
$nome_img = bb_arquivo_tenant(preg_replace('/[ :]+/' , '-' , $nome_img));

if(@$_FILES['foto-sobre']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-sobre']);
	if ($erro_img !== '') { echo $erro_img; exit(); }
	$img_sobre = $nome_img;
	bb_imagem_salvar_upload($_FILES['foto-sobre']['tmp_name'], '../../images/' . $nome_img, 1600);
}

if(@$_FILES['foto-icone-site']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-icone-site'], array('png'));
	if ($erro_img !== '') { echo $erro_img; exit(); }
	bb_imagem_salvar_upload($_FILES['foto-icone-site']['tmp_name'], '../../images/' . $arquivo_icone_site, 512);
}

// Atualiza apenas o registro de configuracao usado pelo sistema (primeiro)
$query_id = $pdo->query("SELECT id FROM config ORDER BY id LIMIT 1");
$res_id = $query_id->fetchAll(PDO::FETCH_ASSOC);
$id_config = @$res_id[0]['id'];

$query = $pdo->prepare("UPDATE config SET nome = :nome, email = :email, telefone_whatsapp = :whatsapp, telefone_fixo = :telefone_fixo, endereco = :endereco, logo = :arquivo_logo, icone = :arquivo_icone, logo_rel = :arquivo_logo_rel, tipo_rel = :tipo_rel, instagram = :instagram, tipo_comissao = :tipo_comissao, texto_rodape = :texto_rodape, img_banner_index = :img_banner_index, icone_site = :arquivo_icone_site, imagem_sobre = :img_sobre, texto_sobre = :texto_sobre, mapa = :mapa, quantidade_cartoes = :quantidade_cartoes, texto_fidelidade = :texto_fidelidade, texto_agendamento = :texto_agendamento, msg_agendamento = :msg_agendamento, segmento = :segmento WHERE id = :id_config");

$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":whatsapp", "$whatsapp");
$query->bindValue(":telefone_fixo", "$fixo");
$query->bindValue(":endereco", "$endereco");
$query->bindValue(":instagram", "$instagram");
$query->bindValue(":texto_rodape", "$texto_rodape");
$query->bindValue(":texto_sobre", "$texto_sobre");
$query->bindValue(":mapa", "$mapa");
$query->bindValue(":texto_fidelidade", "$texto_fidelidade");
$query->bindValue(":texto_agendamento", "$texto_agendamento");
$query->bindValue(":msg_agendamento", "$msg_agendamento");
$query->bindValue(":arquivo_logo", "$arquivo_logo");
$query->bindValue(":arquivo_icone", "$arquivo_icone");
$query->bindValue(":arquivo_logo_rel", "$arquivo_logo_rel");
$query->bindValue(":tipo_rel", "$tipo_rel");
$query->bindValue(":tipo_comissao", "$tipo_comissao");
$query->bindValue(":img_banner_index", "$img_banner_index");
$query->bindValue(":arquivo_icone_site", "$arquivo_icone_site");
$query->bindValue(":img_sobre", "$img_sobre");
$query->bindValue(":quantidade_cartoes", "$quantidade_cartoes");
$query->bindValue(":segmento", "$segmento");
$query->bindValue(":id_config", "$id_config");

$query->execute();

// ============================================================
// CONTEUDO DO SALAO DE BELEZA (nome, imagens e textos do tema salao)
// ============================================================
$nome_salao              = isset($_POST['nome_salao']) ? $_POST['nome_salao'] : '';
$texto_sobre_salao       = isset($_POST['texto_sobre_salao']) ? $_POST['texto_sobre_salao'] : '';
$texto_rodape_salao      = isset($_POST['texto_rodape_salao']) ? $_POST['texto_rodape_salao'] : '';
$texto_agendamento_salao = isset($_POST['texto_agendamento_salao']) ? $_POST['texto_agendamento_salao'] : '';
$whatsapp_salao          = isset($_POST['whatsapp_salao']) ? $_POST['whatsapp_salao'] : '';
$endereco_salao          = isset($_POST['endereco_salao']) ? $_POST['endereco_salao'] : '';
$instagram_salao         = isset($_POST['instagram_salao']) ? $_POST['instagram_salao'] : '';
$email_salao             = isset($_POST['email_salao']) ? $_POST['email_salao'] : '';
$texto_fidelidade_salao  = isset($_POST['texto_fidelidade_salao']) ? $_POST['texto_fidelidade_salao'] : '';

$salao_atual = bb_dados_salao($pdo);
$logo_salao     = ($salao_atual && !empty($salao_atual['logo'])) ? $salao_atual['logo'] : 'logo-salao.png';
$icone_salao    = ($salao_atual && !empty($salao_atual['icone_site'])) ? $salao_atual['icone_site'] : 'salao-favicon.png';

$nome_img_salao = preg_replace('/[ :]+/', '-', @$_FILES['foto-logo-salao']['name']);
if(@$_FILES['foto-logo-salao']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-logo-salao'], array('png'));
	if ($erro_img !== '') { echo $erro_img; exit(); }
	$logo_salao = $nome_img_salao;
	bb_imagem_salvar_upload($_FILES['foto-logo-salao']['tmp_name'], '../img/' . $nome_img_salao, 512);
}

$nome_img_salao = preg_replace('/[ :]+/', '-', @$_FILES['foto-icone-salao']['name']);
if(@$_FILES['foto-icone-salao']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-icone-salao'], array('png'));
	if ($erro_img !== '') { echo $erro_img; exit(); }
	$icone_salao = $nome_img_salao;
	bb_imagem_salvar_upload($_FILES['foto-icone-salao']['tmp_name'], '../../images/' . $nome_img_salao, 512);
}
$img_banner_salao = ($salao_atual && !empty($salao_atual['img_banner_index'])) ? $salao_atual['img_banner_index'] : 'salao-banner.jpg';
$img_sobre_salao  = ($salao_atual && !empty($salao_atual['imagem_sobre'])) ? $salao_atual['imagem_sobre'] : 'salao-sobre.jpg';

$nome_img_salao = preg_replace('/[ :]+/', '-', @$_FILES['foto-banner-salao']['name']);
if(@$_FILES['foto-banner-salao']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-banner-salao']);
	if ($erro_img !== '') { echo $erro_img; exit(); }
	$img_banner_salao = $nome_img_salao;
	bb_imagem_salvar_upload($_FILES['foto-banner-salao']['tmp_name'], '../../images/' . $nome_img_salao, 1920);
}

$nome_img_salao = preg_replace('/[ :]+/', '-', @$_FILES['foto-sobre-salao']['name']);
if(@$_FILES['foto-sobre-salao']['name'] != ""){
	$erro_img = bb_imagem_upload_valida($_FILES['foto-sobre-salao']);
	if ($erro_img !== '') { echo $erro_img; exit(); }
	$img_sobre_salao = $nome_img_salao;
	bb_imagem_salvar_upload($_FILES['foto-sobre-salao']['tmp_name'], '../../images/' . $nome_img_salao, 1600);
}

if ($nome_salao !== '') {
	if ($salao_atual) {
		$query_salao = $pdo->prepare("UPDATE config_salao SET nome = :nome, img_banner_index = :banner, imagem_sobre = :sobre, texto_sobre = :texto_sobre, texto_rodape = :texto_rodape, texto_agendamento = :texto_agendamento, whatsapp = :whatsapp, endereco = :endereco, instagram = :instagram, email = :email, icone_site = :icone_site, logo = :logo, logo_rel = :logo, texto_fidelidade = :texto_fidelidade WHERE id = :id");
		$query_salao->bindValue(":id", $salao_atual['id']);
	} else {
		$query_salao = $pdo->prepare("INSERT INTO config_salao (nome, img_banner_index, imagem_sobre, texto_sobre, texto_rodape, texto_agendamento, whatsapp, endereco, instagram, email, icone_site, logo, logo_rel, texto_fidelidade) VALUES (:nome, :banner, :sobre, :texto_sobre, :texto_rodape, :texto_agendamento, :whatsapp, :endereco, :instagram, :email, :icone_site, :logo, :logo, :texto_fidelidade)");
	}
	$query_salao->bindValue(":nome", $nome_salao);
	$query_salao->bindValue(":banner", $img_banner_salao);
	$query_salao->bindValue(":sobre", $img_sobre_salao);
	$query_salao->bindValue(":texto_sobre", $texto_sobre_salao);
	$query_salao->bindValue(":texto_rodape", $texto_rodape_salao);
	$query_salao->bindValue(":texto_agendamento", $texto_agendamento_salao);
	$query_salao->bindValue(":whatsapp", $whatsapp_salao);
	$query_salao->bindValue(":endereco", $endereco_salao);
	$query_salao->bindValue(":instagram", $instagram_salao);
	$query_salao->bindValue(":email", $email_salao);
	$query_salao->bindValue(":icone_site", $icone_salao);
	$query_salao->bindValue(":logo", $logo_salao);
	$query_salao->bindValue(":texto_fidelidade", $texto_fidelidade_salao);
	$query_salao->execute();
}

echo 'Editado com Sucesso';
 ?>