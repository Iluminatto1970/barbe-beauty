<?php
// ============================================================
// LANDING DE ABERTURA
// ------------------------------------------------------------
// Pagina que carrega ANTES do site atual: hero, servicos,
// profissionais (com foto), depoimentos e contato. O botao
// "Entrar no site" aponta para index.php?site=1, que grava o
// cookie bb_landing e libera o site normal nas proximas visitas.
// Pode ser vista de novo a qualquer momento em /landing.php.
// ============================================================
require_once(__DIR__ . '/sistema/conexao.php');

if (!function_exists('lp_e')) {
    function lp_e($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

$lp_seg = (isset($segmento) && $segmento === 'salao') ? 'salao' : 'barbearia';
$lp_nome = isset($nome_sistema) ? (string) $nome_sistema : 'Barbearia PRO';
$lp_banner = isset($img_banner_index) ? (string) $img_banner_index : 'foto_barbearia.jpg';
$lp_logo = isset($logo_sistema) ? (string) $logo_sistema : '';
$lp_endereco = isset($endereco_sistema) ? (string) $endereco_sistema : '';
$lp_whats = isset($tel_whatsapp) ? (string) $tel_whatsapp : '';
$lp_instagram = isset($instagram_sistema) ? (string) $instagram_sistema : '';
$lp_sobre = isset($texto_sobre) ? trim(strip_tags((string) $texto_sobre)) : '';
$lp_dourado = ($lp_seg === 'salao') ? '#c9a0a6' : '#d9b26a';

// Quando o estabelecimento ainda nao trocou o texto padrao do painel,
// a landing usa um texto proprio em vez de mostrar o placeholder.
if ($lp_sobre === '' || stripos($lp_sobre, 'Este texto pode ser alterado no painel') !== false) {
    $lp_sobre = ($lp_seg === 'salao')
        ? 'Cabelo, unhas e estética com profissionais experientes e um atendimento pensado para você.'
        : 'Cortes clássicos e modernos, barba feita no detalhe e um atendimento que respeita o seu tempo.';
}

// ---- Dados exibidos na landing ----
$lp_servicos = array();
$lp_profissionais = array();
$lp_depoimentos = array();

try {
    $q = $pdo->prepare("SELECT * FROM servicos WHERE ativo = 'Sim' AND segmento = :seg ORDER BY id ASC LIMIT 6");
    $q->execute(array(':seg' => $lp_seg));
    $lp_servicos = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $lp_servicos = array();
}

try {
    $q = $pdo->query("SELECT * FROM usuarios WHERE atendimento = 'Sim' AND ativo = 'Sim'" . bb_filtro_profissionais() . " ORDER BY id ASC LIMIT 6");
    $lp_profissionais = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $lp_profissionais = array();
}

try {
    $sql_dep = "SELECT * FROM comentarios WHERE ativo = 'Sim'"
        . " AND (segmento IS NULL OR segmento = '' OR segmento = 'ambos' OR segmento = :seg)"
        . " ORDER BY id DESC LIMIT 6";
    $q = $pdo->prepare($sql_dep);
    $q->execute(array(':seg' => $lp_seg));
    $lp_depoimentos = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $lp_depoimentos = array();
}

// Foto do profissional: so usa o arquivo se ele existir mesmo.
function lp_prof_foto($foto)
{
    $foto = trim((string) $foto);
    if ($foto === '' || $foto === 'sem-foto.jpg') {
        return '';
    }
    if (!is_file(__DIR__ . '/sistema/painel/img/perfil/' . $foto)) {
        return '';
    }
    return 'sistema/painel/img/perfil/' . implode('/', array_map('rawurlencode', explode('/', $foto)));
}

function lp_iniciais($nome)
{
    $partes = preg_split('/\s+/', trim((string) $nome));
    $ini = mb_strtoupper(mb_substr($partes[0], 0, 1, 'UTF-8'), 'UTF-8');
    if (count($partes) > 1) {
        $ini .= mb_strtoupper(mb_substr(end($partes), 0, 1, 'UTF-8'), 'UTF-8');
    }
    return $ini;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?php echo lp_e($lp_nome); ?></title>
  <meta name="description" content="<?php echo lp_e($lp_sobre); ?>">
  <?php if ($lp_logo !== '') { ?>
  <link rel="shortcut icon" href="images/<?php echo lp_e($lp_logo); ?>" type="image/x-icon">
  <?php } ?>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --lp-dourado: <?php echo $lp_dourado; ?>;
      --lp-fundo: #0b1114;
      --lp-fundo-2: #101a1f;
      --lp-card: rgba(255, 255, 255, .04);
      --lp-borda: rgba(255, 255, 255, .1);
      --lp-texto: #eef2f4;
      --lp-fraco: #a7b3ba;
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
      background: var(--lp-fundo);
      color: var(--lp-texto);
      font-family: 'Poppins', system-ui, -apple-system, sans-serif;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }
    a { color: inherit; text-decoration: none; }
    img { max-width: 100%; display: block; }
    .lp-wrap { width: 100%; max-width: 1180px; margin: 0 auto; padding: 0 22px; }
    h1, h2, h3 { font-family: 'Playfair Display', Georgia, serif; font-weight: 600; line-height: 1.15; margin: 0; }
    .lp-kicker {
      display: inline-block; font-size: 12px; letter-spacing: 3px; text-transform: uppercase;
      color: var(--lp-dourado); font-weight: 600; margin-bottom: 16px;
    }
    .lp-btn {
      display: inline-flex; align-items: center; justify-content: center; gap: 10px;
      padding: 15px 30px; border-radius: 999px; font-size: 15px; font-weight: 600;
      border: 1px solid transparent; transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
      cursor: pointer;
    }
    .lp-btn:hover { transform: translateY(-2px); }
    .lp-btn-principal {
      background: var(--lp-dourado); color: #14100a;
      box-shadow: 0 14px 34px rgba(0, 0, 0, .35);
    }
    .lp-btn-secundario { border-color: var(--lp-borda); background: rgba(255,255,255,.03); color: var(--lp-texto); }
    .lp-btn-secundario:hover { background: rgba(255,255,255,.08); }

    /* ---------- topo ---------- */
    .lp-topo {
      position: absolute; top: 0; left: 0; right: 0; z-index: 5;
      padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,.08);
      background: linear-gradient(180deg, rgba(6,10,12,.75), rgba(6,10,12,0));
    }
    .lp-topo .lp-wrap { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .lp-marca { display: flex; align-items: center; gap: 12px; font-family: 'Playfair Display', serif; font-size: 20px; letter-spacing: .5px; }
    .lp-marca img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 1px solid var(--lp-borda); }
    .lp-topo-links { display: flex; align-items: center; gap: 22px; font-size: 14px; color: var(--lp-fraco); }
    .lp-topo-links a:hover { color: var(--lp-dourado); }
    .lp-topo-cta { padding: 10px 22px; font-size: 14px; }
    @media (max-width: 720px) { .lp-topo-links > a:not(.lp-topo-cta) { display: none; } }

    /* ---------- hero ---------- */
    .lp-hero {
      position: relative; min-height: 92vh; display: flex; align-items: center;
      padding: 140px 0 90px; overflow: hidden;
    }
    .lp-hero-bg { position: absolute; inset: 0; }
    .lp-hero-bg img { width: 100%; height: 100%; object-fit: cover; }
    .lp-hero-bg::after {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(100deg, rgba(6,10,12,.94) 12%, rgba(6,10,12,.72) 55%, rgba(6,10,12,.45) 100%);
    }
    .lp-hero .lp-wrap { position: relative; z-index: 2; }
    .lp-hero-conteudo { max-width: 660px; }
    .lp-hero h1 { font-size: clamp(38px, 6vw, 68px); margin-bottom: 22px; }
    .lp-hero h1 span { color: var(--lp-dourado); }
    .lp-hero p { font-size: 17px; color: var(--lp-fraco); max-width: 520px; margin: 0 0 34px; }
    .lp-hero-acoes { display: flex; flex-wrap: wrap; gap: 14px; }
    .lp-hero-selos { display: flex; flex-wrap: wrap; gap: 26px; margin-top: 46px; font-size: 13px; color: var(--lp-fraco); }
    .lp-hero-selos span { display: inline-flex; align-items: center; gap: 8px; }
    .lp-hero-selos b { color: var(--lp-dourado); font-size: 16px; }

    /* ---------- faixa ---------- */
    .lp-faixa { background: var(--lp-fundo-2); border-top: 1px solid var(--lp-borda); border-bottom: 1px solid var(--lp-borda); padding: 26px 0; }
    .lp-faixa .lp-wrap { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; text-align: center; }
    .lp-faixa strong { display: block; font-size: 15px; margin-bottom: 4px; }
    .lp-faixa small { color: var(--lp-fraco); font-size: 13px; }
    @media (max-width: 720px) { .lp-faixa .lp-wrap { grid-template-columns: 1fr; gap: 18px; } }

    /* ---------- secoes ---------- */
    .lp-secao { padding: 96px 0; }
    .lp-secao-alt { background: var(--lp-fundo-2); }
    .lp-secao-cabecalho { text-align: center; max-width: 620px; margin: 0 auto 52px; }
    .lp-secao-cabecalho h2 { font-size: clamp(28px, 4vw, 42px); margin-bottom: 14px; }
    .lp-secao-cabecalho p { color: var(--lp-fraco); margin: 0; font-size: 16px; }

    .lp-grade { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    @media (max-width: 960px) { .lp-grade { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 620px) { .lp-grade { grid-template-columns: 1fr; } }

    .lp-card {
      background: var(--lp-card); border: 1px solid var(--lp-borda); border-radius: 18px;
      overflow: hidden; display: flex; flex-direction: column;
      transition: transform .25s ease, border-color .25s ease;
    }
    .lp-card:hover { transform: translateY(-6px); border-color: rgba(217,178,106,.45); }
    .lp-card-img { position: relative; aspect-ratio: 4 / 3; background: #0d1518; overflow: hidden; }
    .lp-card-img img { width: 100%; height: 100%; object-fit: cover; }
    .lp-card-corpo { padding: 20px 20px 22px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .lp-card-corpo h3 { font-size: 20px; }
    .lp-card-corpo p { color: var(--lp-fraco); font-size: 14px; margin: 0; }
    .lp-preco { font-family: 'Playfair Display', serif; color: var(--lp-dourado); font-size: 22px; margin-top: auto; }

    /* profissionais */
    .lp-prof { text-align: center; align-items: center; }
    .lp-prof-foto { aspect-ratio: 1 / 1; }
    .lp-prof-foto img { object-position: center 20%; }
    .lp-avatar {
      display: flex; align-items: center; justify-content: center;
      font-family: 'Playfair Display', serif; font-size: 44px; color: #fff;
      width: 100%; height: 100%; background: linear-gradient(135deg, #1b2a30, #0d1518);
      letter-spacing: 2px;
    }
    .lp-prof .lp-card-corpo { align-items: center; text-align: center; }
    .lp-prof-cargo { color: var(--lp-dourado); font-size: 12px; letter-spacing: 2px; text-transform: uppercase; }
    .lp-link { display: inline-block; margin-top: 12px; font-size: 14px; font-weight: 600; color: var(--lp-dourado); }
    .lp-link:hover { text-decoration: underline; }

    /* depoimentos */
    .lp-dep { padding: 24px; }
    .lp-dep p { font-size: 15px; color: #dfe6ea; font-style: italic; margin: 0 0 18px; }
    .lp-dep-autor { display: flex; align-items: center; gap: 12px; }
    .lp-dep-autor img, .lp-dep-autor .lp-avatar-mini {
      width: 42px; height: 42px; border-radius: 50%; object-fit: cover; flex: 0 0 42px;
    }
    .lp-avatar-mini {
      display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 600;
      background: #1b2a30; color: var(--lp-dourado);
    }
    .lp-dep-autor strong { font-size: 14px; display: block; }
    .lp-estrelas { color: var(--lp-dourado); font-size: 12px; letter-spacing: 3px; }

    /* contato */
    .lp-contato { display: grid; grid-template-columns: 1.1fr .9fr; gap: 32px; align-items: center; }
    @media (max-width: 900px) { .lp-contato { grid-template-columns: 1fr; } }
    .lp-contato h2 { font-size: clamp(28px, 4vw, 40px); margin-bottom: 16px; }
    .lp-contato p { color: var(--lp-fraco); margin: 0 0 26px; }
    .lp-lista { list-style: none; padding: 0; margin: 0; display: grid; gap: 16px; }
    .lp-lista li { display: flex; gap: 14px; align-items: flex-start; font-size: 15px; }
    .lp-icone {
      flex: 0 0 40px; width: 40px; height: 40px; border-radius: 12px;
      background: rgba(217,178,106,.12); color: var(--lp-dourado);
      display: flex; align-items: center; justify-content: center; font-size: 17px;
    }
    .lp-lista small { display: block; color: #8b979e; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 2px; }
    .lp-lista a { border-bottom: 1px solid transparent; }
    .lp-lista a:hover { border-color: var(--lp-dourado); }

    /* cta final */
    .lp-cta { text-align: center; padding: 90px 0; background: linear-gradient(135deg, #0f1c22, #0a1215); border-top: 1px solid var(--lp-borda); }
    .lp-cta h2 { font-size: clamp(28px, 4vw, 44px); margin-bottom: 16px; }
    .lp-cta p { color: var(--lp-fraco); max-width: 520px; margin: 0 auto 30px; }

    /* rodape */
    .lp-rodape { padding: 30px 0; border-top: 1px solid var(--lp-borda); font-size: 13px; color: #7f8b92; }
    .lp-rodape .lp-wrap { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
    .lp-rodape a:hover { color: var(--lp-dourado); }
  </style>
</head>
<body>

  <header class="lp-topo">
    <div class="lp-wrap">
      <a class="lp-marca" href="landing.php">
        <?php if ($lp_logo !== '' && is_file(__DIR__ . '/images/' . $lp_logo)) { ?>
        <img src="images/<?php echo lp_e($lp_logo); ?>" alt="<?php echo lp_e($lp_nome); ?>">
        <?php } ?>
        <span><?php echo lp_e($lp_nome); ?></span>
      </a>
      <nav class="lp-topo-links">
        <a href="#servicos">Serviços</a>
        <a href="#profissionais">Profissionais</a>
        <a href="#contato">Contato</a>
        <a class="lp-btn lp-btn-principal lp-topo-cta" href="index.php?site=1">Entrar no site</a>
      </nav>
    </div>
  </header>

  <section class="lp-hero">
    <div class="lp-hero-bg">
      <?php if (is_file(__DIR__ . '/images/' . $lp_banner)) { ?>
      <img src="images/<?php echo lp_e($lp_banner); ?>" alt="">
      <?php } ?>
    </div>
    <div class="lp-wrap">
      <div class="lp-hero-conteudo">
        <span class="lp-kicker"><?php echo $lp_seg === 'salao' ? 'Salão de beleza' : 'Barbearia'; ?> · <?php echo lp_e($lp_nome); ?></span>
        <h1><?php echo $lp_seg === 'salao' ? 'Sua beleza em <span>boas mãos</span>.' : 'Seu estilo, feito com <span>precisão</span>.'; ?></h1>
        <p><?php echo lp_e($lp_sobre); ?> Agende em segundos, escolha seu profissional e chegue sem esperar.</p>
        <div class="lp-hero-acoes">
          <a class="lp-btn lp-btn-principal" href="agendamentos">Agendar horário</a>
          <a class="lp-btn lp-btn-secundario" href="#servicos">Ver serviços</a>
        </div>
        <div class="lp-hero-selos">
          <?php if (!empty($lp_servicos)) { ?>
          <span><b><?php echo count($lp_servicos); ?></b> serviços à disposição</span>
          <?php } ?>
          <?php if (!empty($lp_profissionais)) { ?>
          <span><b><?php echo count($lp_profissionais); ?></b> profissionais</span>
          <?php } ?>
          <?php if ($lp_whats !== '') { ?>
          <span>Atendimento por WhatsApp</span>
          <?php } ?>
        </div>
      </div>
    </div>
  </section>

  <section class="lp-faixa">
    <div class="lp-wrap">
      <div><strong>Agendamento online</strong><small>Escolha serviço, profissional e horário</small></div>
      <div><strong>Profissionais experientes</strong><small>Cuidado e atenção em cada detalhe</small></div>
      <div><strong>Ambiente acolhedor</strong><small>Venha relaxar e sair com autoestima em alta</small></div>
    </div>
  </section>

  <?php if (!empty($lp_servicos)) { ?>
  <section class="lp-secao" id="servicos">
    <div class="lp-wrap">
      <div class="lp-secao-cabecalho">
        <span class="lp-kicker">Serviços</span>
        <h2>O que fazemos por você</h2>
        <p>Preços claros e atendimento profissional. Escolha o seu e reserve o horário.</p>
      </div>
      <div class="lp-grade">
        <?php foreach ($lp_servicos as $s) {
            $s_foto = trim((string) $s['foto']);
            $s_url = '';
            if ($s_foto !== '' && is_file(__DIR__ . '/sistema/painel/img/servicos/' . $s_foto)) {
                $s_url = 'sistema/painel/img/servicos/' . implode('/', array_map('rawurlencode', explode('/', $s_foto)));
            }
            $s_preco = number_format((float) $s['valor'], 2, ',', '.');
        ?>
        <div class="lp-card">
          <div class="lp-card-img">
            <?php if ($s_url !== '') { ?>
            <img src="<?php echo lp_e($s_url); ?>" alt="<?php echo lp_e($s['nome']); ?>" loading="lazy">
            <?php } ?>
          </div>
          <div class="lp-card-corpo">
            <h3><?php echo lp_e($s['nome']); ?></h3>
            <span class="lp-preco">R$ <?php echo $s_preco; ?></span>
            <a class="lp-link" href="agendamentos">Agendar serviço</a>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </section>
  <?php } ?>

  <?php if (!empty($lp_profissionais)) { ?>
  <section class="lp-secao lp-secao-alt" id="profissionais">
    <div class="lp-wrap">
      <div class="lp-secao-cabecalho">
        <span class="lp-kicker">Equipe</span>
        <h2>Quem vai cuidar de você</h2>
        <p>Nossa equipe está pronta para o seu próximo atendimento.</p>
      </div>
      <div class="lp-grade">
        <?php foreach ($lp_profissionais as $p) {
            $p_url = lp_prof_foto($p['foto']);
        ?>
        <div class="lp-card lp-prof">
          <div class="lp-card-img lp-prof-foto">
            <?php if ($p_url !== '') { ?>
            <img src="<?php echo lp_e($p_url); ?>" alt="<?php echo lp_e($p['nome']); ?>" loading="lazy">
            <?php } else { ?>
            <div class="lp-avatar"><?php echo lp_e(lp_iniciais($p['nome'])); ?></div>
            <?php } ?>
          </div>
          <div class="lp-card-corpo">
            <h3><?php echo lp_e($p['nome']); ?></h3>
            <span class="lp-prof-cargo"><?php echo lp_e($p['nivel']); ?></span>
            <a class="lp-link" href="agendamentos">Agendar com <?php echo lp_e(strtok($p['nome'], ' ')); ?></a>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </section>
  <?php } ?>

  <?php if (!empty($lp_depoimentos)) { ?>
  <section class="lp-secao">
    <div class="lp-wrap">
      <div class="lp-secao-cabecalho">
        <span class="lp-kicker">Depoimentos</span>
        <h2>Quem atendemos recomenda</h2>
      </div>
      <div class="lp-grade">
        <?php foreach ($lp_depoimentos as $d) {
            $d_foto = trim((string) $d['foto']);
            $d_url = '';
            if ($d_foto !== '' && $d_foto !== 'sem-foto.jpg' && is_file(__DIR__ . '/sistema/painel/img/comentarios/' . $d_foto)) {
                $d_url = 'sistema/painel/img/comentarios/' . implode('/', array_map('rawurlencode', explode('/', $d_foto)));
            }
        ?>
        <div class="lp-card lp-dep">
          <span class="lp-estrelas">★★★★★</span>
          <p>“<?php echo lp_e($d['texto']); ?>”</p>
          <div class="lp-dep-autor">
            <?php if ($d_url !== '') { ?>
            <img src="<?php echo lp_e($d_url); ?>" alt="<?php echo lp_e($d['nome']); ?>" loading="lazy">
            <?php } else { ?>
            <span class="lp-avatar-mini"><?php echo lp_e(lp_iniciais($d['nome'])); ?></span>
            <?php } ?>
            <strong><?php echo lp_e($d['nome']); ?></strong>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
  </section>
  <?php } ?>

  <section class="lp-secao lp-secao-alt" id="contato">
    <div class="lp-wrap">
      <div class="lp-contato">
        <div>
          <span class="lp-kicker">Contato</span>
          <h2>Venha nos visitar</h2>
          <p>Prefere falar direto? Chame no WhatsApp ou nos acompanhe no Instagram.</p>
          <a class="lp-btn lp-btn-principal" href="agendamentos">Agendar meu horário</a>
        </div>
        <ul class="lp-lista">
          <?php if ($lp_endereco !== '') { ?>
          <li><span class="lp-icone">⌖</span><div><small>Endereço</small><?php echo lp_e($lp_endereco); ?></div></li>
          <?php } ?>
          <?php if ($lp_whats !== '') { ?>
          <li><span class="lp-icone">☎</span><div><small>WhatsApp</small><a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo lp_e($lp_whats); ?>" target="_blank" rel="noopener"><?php echo lp_e($lp_whats); ?></a></div></li>
          <?php } ?>
          <?php if ($lp_instagram !== '') { ?>
          <li><span class="lp-icone">◎</span><div><small>Instagram</small><a href="<?php echo lp_e($lp_instagram); ?>" target="_blank" rel="noopener"><?php echo lp_e($lp_instagram); ?></a></div></li>
          <?php } ?>
          <li><span class="lp-icone">◷</span><div><small>Agendamentos</small>Online, 24 horas por dia</div></li>
        </ul>
      </div>
    </div>
  </section>

  <section class="lp-cta">
    <div class="lp-wrap">
      <h2><?php echo $lp_seg === 'salao' ? 'Seu próximo look começa aqui' : 'Seu próximo corte começa aqui'; ?></h2>
      <p>Reserve em menos de um minuto e garanta o melhor horário do dia.</p>
      <a class="lp-btn lp-btn-principal" href="agendamentos">Agendar agora</a>
    </div>
  </section>

  <footer class="lp-rodape">
    <div class="lp-wrap">
      <span>&copy; <?php echo date('Y'); ?> <?php echo lp_e($lp_nome); ?>. Todos os direitos reservados.</span>
      <span><a href="index.php?site=1">Entrar no site</a> · <a href="sistema" target="_blank" rel="noopener">Área do estabelecimento</a></span>
    </div>
  </footer>

</body>
</html>
