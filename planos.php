<?php
// ============================================================
// PAGINA DE VENDAS DO SISTEMA (publica)
// ------------------------------------------------------------
// Existe apenas na instalacao principal: no dominio de um
// cliente quem responde e o site dele.
// Planos e precos vem de saas_planos; o modo gratuito e
// explicado com o limite real de agendamentos do plano.
// ============================================================
require_once(__DIR__ . '/sistema/conexao.php');

if (bb_banco_atual() !== bb_banco_controle()) {
    header('Location: ./', true, 302);
    exit;
}

$bbv_planos = array();
try {
    $bbv_q = $pdo_controle->query("SELECT * FROM saas_planos WHERE ativo = 'Sim' ORDER BY valor ASC");
    if ($bbv_q) {
        $bbv_planos = $bbv_q->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $bbv_planos = array();
}

$bbv_gratis = null;
$bbv_pagos = array();
foreach ($bbv_planos as $bbv_p) {
    if ((float) $bbv_p['valor'] <= 0) {
        if ($bbv_gratis === null) {
            $bbv_gratis = $bbv_p;
        }
    } else {
        $bbv_pagos[] = $bbv_p;
    }
}

$bbv_limite_gratis = ($bbv_gratis && (int) $bbv_gratis['limite_agendamentos'] > 0)
    ? (int) $bbv_gratis['limite_agendamentos']
    : null;
$bbv_limite_prof_gratis = ($bbv_gratis && (int) $bbv_gratis['limite_profissionais'] > 0)
    ? (int) $bbv_gratis['limite_profissionais']
    : null;

$bbv_preco_pago = null;
$bbv_plano_pago = null;
foreach ($bbv_pagos as $bbv_p) {
    if ($bbv_preco_pago === null || (float) $bbv_p['valor'] < $bbv_preco_pago) {
        $bbv_preco_pago = (float) $bbv_p['valor'];
        $bbv_plano_pago = $bbv_p;
    }
}
if ($bbv_preco_pago === null) {
    $bbv_preco_pago = 49.00;
}

function bbv_brl($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function bbv_limite($limite, $singular, $plural)
{
    $limite = (int) $limite;
    if ($limite <= 0) {
        return ucfirst($plural) . ' ilimitados';
    }
    return $limite . ' ' . ($limite === 1 ? $singular : $plural);
}

$bbv_agendamentos_gratis = $bbv_limite_gratis !== null
    ? $bbv_limite_gratis . ' agendamentos por mês'
    : 'agendamentos ilimitados';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Barbearia PRO · sistema completo para barbearias e salões</title>
  <meta name="description" content="Agenda online, site próprio, clientes, produtos, financeiro, comissões e relatórios em um só sistema. Comece grátis, sem cartão.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #d4af37;
      --gold-light: #f0cd7f;
      --bg: #070707;
      --bg-soft: #0f0f0f;
      --panel: #131313;
      --line: #262626;
      --text: #ededed;
      --muted: #9ca3af;
      --ok: #34d399;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0; background: var(--bg); color: var(--text);
      font-family: Poppins, -apple-system, Segoe UI, Roboto, sans-serif;
      font-size: 16px; line-height: 1.6;
    }
    a { color: var(--gold-light); text-decoration: none; }
    a:hover { color: var(--gold); }
    img { max-width: 100%; }
    .wrap { max-width: 1140px; margin: 0 auto; padding: 0 20px; }
    .nav {
      position: sticky; top: 0; z-index: 20; backdrop-filter: blur(12px);
      background: rgba(7, 7, 7, .82); border-bottom: 1px solid var(--line);
    }
    .nav-inner { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 0; }
    .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; letter-spacing: .2px; color: var(--text); }
    .brand-mark {
      width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center;
      background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: #171204; font-size: 17px;
    }
    .nav-links { display: flex; align-items: center; gap: 22px; font-size: 14px; }
    .nav-links a { color: var(--muted); }
    .nav-links a:hover { color: var(--text); }
    @media (max-width: 780px) { .nav-links { display: none; } }

    .btn {
      display: inline-flex; align-items: center; justify-content: center; gap: 8px;
      padding: 13px 22px; border-radius: 10px; font-weight: 600; font-size: 15px;
      border: 1px solid transparent; cursor: pointer; text-align: center;
    }
    .btn-gold { background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: #171204; }
    .btn-gold:hover { color: #171204; transform: translateY(-1px); }
    .btn-ghost { border-color: var(--line); color: var(--text); background: transparent; }
    .btn-ghost:hover { border-color: var(--gold); color: var(--text); }
    .btn-sm { padding: 10px 16px; font-size: 13.5px; }

    .hero { position: relative; padding: 74px 0 50px; overflow: hidden; }
    .hero:before {
      content: ""; position: absolute; inset: -40% -10% auto -10%; height: 560px;
      background: radial-gradient(600px 300px at 20% 20%, rgba(212, 175, 55, .18), transparent 70%);
      pointer-events: none;
    }
    .hero-grid { position: relative; display: grid; grid-template-columns: 1.05fr .95fr; gap: 42px; align-items: center; }
    @media (max-width: 940px) { .hero-grid { grid-template-columns: 1fr; gap: 30px; } }
    .eyebrow {
      display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px;
      border: 1px solid rgba(212, 175, 55, .35); background: rgba(212, 175, 55, .08);
      color: var(--gold-light); font-size: 12.5px; letter-spacing: .3px; text-transform: uppercase;
    }
    h1 {
      font-family: "Playfair Display", Georgia, serif; font-weight: 600;
      font-size: clamp(32px, 4.4vw, 52px); line-height: 1.12; margin: 18px 0 16px; letter-spacing: -.5px;
    }
    h1 em { color: var(--gold-light); font-style: normal; }
    .lead { color: var(--muted); font-size: 17px; max-width: 540px; margin: 0 0 26px; }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
    .hero-note { margin-top: 16px; font-size: 12.5px; color: var(--muted); display: flex; flex-wrap: wrap; gap: 14px; }
    .hero-note span:before { content: "✓"; color: var(--ok); margin-right: 6px; }

    .mock {
      border: 1px solid var(--line); border-radius: 16px; background: linear-gradient(180deg, #151515, #0d0d0d);
      box-shadow: 0 30px 80px rgba(0, 0, 0, .55); overflow: hidden;
    }
    .mock-top { display: flex; align-items: center; gap: 7px; padding: 11px 14px; border-bottom: 1px solid var(--line); }
    .dot { width: 9px; height: 9px; border-radius: 50%; background: #333; }
    .mock-url { margin-left: 8px; font-size: 11.5px; color: var(--muted); }
    .mock-body { padding: 18px; display: grid; gap: 12px; }
    .mock-row { display: grid; grid-template-columns: 46px 1fr auto; gap: 12px; align-items: center;
      padding: 11px 12px; border: 1px solid var(--line); border-radius: 10px; background: #101010; }
    .mock-avatar { width: 38px; height: 38px; border-radius: 10px; background: #1d1d1d; display: grid; place-items: center; font-size: 16px; }
    .mock-line { height: 9px; border-radius: 6px; background: #232323; }
    .mock-line.short { width: 55%; margin-top: 7px; }
    .mock-pill { font-size: 11px; color: #171204; background: var(--gold); padding: 3px 9px; border-radius: 999px; font-weight: 600; }
    .mock-pill.alt { background: #1f2a24; color: var(--ok); }

    .strip { border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); background: var(--bg-soft); }
    .strip-inner { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; padding: 22px 0; text-align: center; }
    @media (max-width: 780px) { .strip-inner { grid-template-columns: repeat(2, 1fr); } }
    .strip strong { display: block; font-size: 18px; }
    .strip span { font-size: 12.5px; color: var(--muted); }

    section { padding: 66px 0; }
    section[id], div[id] { scroll-margin-top: 84px; }
    .section-head { max-width: 620px; margin: 0 auto 34px; text-align: center; }
    .section-head h2 { font-family: "Playfair Display", Georgia, serif; font-size: clamp(26px, 3vw, 36px); margin: 0 0 10px; font-weight: 600; }
    .section-head p { color: var(--muted); margin: 0; }

    .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    @media (max-width: 940px) { .cards { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 620px) { .cards { grid-template-columns: 1fr; } }
    .card {
      border: 1px solid var(--line); border-radius: 14px; padding: 22px; background: var(--panel);
    }
    .card .ico {
      width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center;
      background: rgba(212, 175, 55, .12); color: var(--gold-light); font-size: 18px; margin-bottom: 12px;
    }
    .card h3 { margin: 0 0 6px; font-size: 16px; }
    .card p { margin: 0; color: var(--muted); font-size: 14px; }

    .freemium { background: var(--bg-soft); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
    .freemium-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 26px; align-items: stretch; }
    @media (max-width: 900px) { .freemium-grid { grid-template-columns: 1fr; } }
    .freemium-card { border: 1px solid var(--line); border-radius: 16px; padding: 26px; background: var(--panel); }
    .freemium-card.destaque { border-color: rgba(212, 175, 55, .5); box-shadow: 0 0 0 1px rgba(212, 175, 55, .14) inset; }
    .freemium-card h3 { margin: 0 0 4px; font-size: 20px; }
    .freemium-card .preco { font-size: 30px; font-weight: 700; color: var(--gold-light); margin: 8px 0 2px; }
    .freemium-card .preco small { font-size: 13px; color: var(--muted); font-weight: 400; }
    .check { list-style: none; padding: 0; margin: 16px 0 0; display: grid; gap: 9px; font-size: 14px; }
    .check li { padding-left: 24px; position: relative; color: #d7d7d7; }
    .check li:before { content: "✓"; position: absolute; left: 0; top: 0; color: var(--ok); font-weight: 700; }
    .nota {
      margin-top: 18px; border: 1px dashed #3a3423; background: rgba(212, 175, 55, .07);
      border-radius: 12px; padding: 14px 16px; font-size: 13px; color: #d9cfae;
    }
    .nota strong { color: var(--gold-light); }

    .planos-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 18px; align-items: stretch; }
    .plano {
      position: relative; border: 1px solid var(--line); border-radius: 16px; padding: 26px;
      background: var(--panel); display: flex; flex-direction: column;
      flex: 1 1 300px; max-width: 374px;
    }
    @media (max-width: 700px) { .plano { flex: 1 1 100%; max-width: 460px; } }
    .plano.popular { border-color: var(--gold); box-shadow: 0 18px 50px rgba(212, 175, 55, .12); }
    .plano .tag {
      position: absolute; top: -12px; left: 24px; background: var(--gold); color: #171204;
      font-size: 11px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
      padding: 4px 10px; border-radius: 999px;
    }
    .plano h3 { margin: 0 0 2px; font-size: 18px; }
    .plano .sub { color: var(--muted); font-size: 13px; min-height: 20px; }
    .plano .valor { font-size: 34px; font-weight: 700; margin: 14px 0 0; }
    .plano .valor small { font-size: 13px; color: var(--muted); font-weight: 400; }
    .plano .check { flex: 1; }
    .plano .btn { width: 100%; margin-top: 20px; }

    .tabela-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 560px; }
    th, td { padding: 13px 16px; text-align: left; border-bottom: 1px solid var(--line); }
    th { background: #101010; font-size: 12.5px; text-transform: uppercase; letter-spacing: .4px; color: var(--muted); }
    tbody tr:last-child td { border-bottom: 0; }
    td.yes { color: var(--ok); font-weight: 600; }
    td.no { color: #6b7280; }

    details {
      border: 1px solid var(--line); border-radius: 12px; padding: 16px 18px; background: var(--panel);
      margin-bottom: 10px;
    }
    details summary { cursor: pointer; font-weight: 600; font-size: 15px; }
    details p { color: var(--muted); margin: 10px 0 0; font-size: 14px; }

    .cta-final {
      border: 1px solid rgba(212, 175, 55, .4); border-radius: 20px; padding: 42px 28px; text-align: center;
      background: radial-gradient(700px 260px at 50% 0, rgba(212, 175, 55, .16), transparent 70%), var(--panel);
    }
    .cta-final h2 { font-family: "Playfair Display", Georgia, serif; font-size: clamp(26px, 3vw, 36px); margin: 0 0 10px; }
    .cta-final p { color: var(--muted); margin: 0 auto 22px; max-width: 540px; }

    footer { border-top: 1px solid var(--line); padding: 26px 0 36px; color: var(--muted); font-size: 13px; }
    .foot-inner { display: flex; flex-wrap: wrap; gap: 14px; justify-content: space-between; align-items: center; }
    .foot-links { display: flex; flex-wrap: wrap; gap: 18px; }
  </style>
</head>
<body>

  <header class="nav">
    <div class="wrap nav-inner">
      <a class="brand" href="./"><span class="brand-mark">✂</span> Barbearia PRO</a>
      <nav class="nav-links">
        <a href="#recursos">Recursos</a>
        <a href="#gratis">Plano grátis</a>
        <a href="#planos">Planos</a>
        <a href="#duvidas">Dúvidas</a>
        <a href="sistema/">Entrar</a>
      </nav>
      <a class="btn btn-gold btn-sm" href="assinar.php">Começar grátis</a>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="wrap hero-grid">
        <div>
          <span class="eyebrow">Sistema para barbearias e salões</span>
          <h1>Sua barbearia organizada, <em>do agendamento ao caixa</em>.</h1>
          <p class="lead">
            Agenda online, site próprio, clientes, serviços, produtos, financeiro, comissões e relatórios
            no mesmo lugar. Comece no plano gratuito e só pague quando precisar de mais agendamentos.
          </p>
          <div class="hero-actions">
            <a class="btn btn-gold" href="assinar.php">Criar minha conta grátis</a>
            <a class="btn btn-ghost" href="#planos">Ver planos e preços</a>
          </div>
          <div class="hero-note">
            <span>Sem cartão no plano grátis</span>
            <span><?php echo htmlspecialchars($bbv_agendamentos_gratis); ?></span>
            <span>Cancele quando quiser</span>
          </div>
        </div>

        <div class="mock" aria-hidden="true">
          <div class="mock-top">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            <span class="mock-url">seu-estabelecimento.<?php echo htmlspecialchars(BB_DOMINIO_BASE); ?></span>
          </div>
          <div class="mock-body">
            <div class="mock-row">
              <span class="mock-avatar">✂</span>
              <span><span class="mock-line" style="width:70%"></span><span class="mock-line short"></span></span>
              <span class="mock-pill">09:00</span>
            </div>
            <div class="mock-row">
              <span class="mock-avatar">💈</span>
              <span><span class="mock-line" style="width:58%"></span><span class="mock-line short"></span></span>
              <span class="mock-pill alt">Confirmado</span>
            </div>
            <div class="mock-row">
              <span class="mock-avatar">🧔</span>
              <span><span class="mock-line" style="width:64%"></span><span class="mock-line short"></span></span>
              <span class="mock-pill">10:30</span>
            </div>
            <div class="mock-row">
              <span class="mock-avatar">💅</span>
              <span><span class="mock-line" style="width:52%"></span><span class="mock-line short"></span></span>
              <span class="mock-pill alt">Confirmado</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="strip">
      <div class="wrap strip-inner">
        <div><strong>30 s</strong><span>para criar a conta</span></div>
        <div><strong>Endereço</strong><span>próprio incluído</span></div>
        <div><strong>100%</strong><span>pelo navegador</span></div>
        <div><strong>0</strong><span>instalação necessária</span></div>
      </div>
    </div>

    <section id="recursos">
      <div class="wrap">
        <div class="section-head">
          <h2>Tudo o que a rotina pede, sem planilha</h2>
          <p>Um sistema só, com o site do estabelecimento incluído.</p>
        </div>

        <div class="cards">
          <article class="card">
            <div class="ico">🗓️</div>
            <h3>Agenda online</h3>
            <p>Horários por profissional, confirmação de agendamento e bloqueio de dias e horários.</p>
          </article>
          <article class="card">
            <div class="ico">🌐</div>
            <h3>Site próprio</h3>
            <p>Página com serviços, profissionais, produtos e botão de agendamento no seu endereço.</p>
          </article>
          <article class="card">
            <div class="ico">👥</div>
            <h3>Clientes</h3>
            <p>Cadastro, histórico de atendimentos e retorno automático para não perder cliente.</p>
          </article>
          <article class="card">
            <div class="ico">✂️</div>
            <h3>Serviços e profissionais</h3>
            <p>Tabela de preços, comissão por serviço e permissões de acesso por cargo.</p>
          </article>
          <article class="card">
            <div class="ico">📦</div>
            <h3>Produtos e estoque</h3>
            <p>Entradas e saídas, estoque mínimo e baixa automática nas vendas.</p>
          </article>
          <article class="card">
            <div class="ico">💵</div>
            <h3>Financeiro</h3>
            <p>Contas a pagar e a receber, comprovantes anexados e fluxo de caixa do mês.</p>
          </article>
          <article class="card">
            <div class="ico">🧾</div>
            <h3>Comissões</h3>
            <p>Cálculo por profissional, filtros por período e relatório pronto para pagar.</p>
          </article>
          <article class="card">
            <div class="ico">📈</div>
            <h3>Relatórios em PDF</h3>
            <p>Fechamento, comissões, vendas e listas para imprimir ou enviar ao contador.</p>
          </article>
          <article class="card">
            <div class="ico">🎁</div>
            <h3>Fidelidade</h3>
            <p>Cartão de fidelidade configurável e comentários de clientes no site.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="freemium" id="gratis">
      <div class="wrap">
        <div class="section-head" style="text-align:left;margin-left:0;max-width:720px">
          <h2>O plano gratuito é de verdade</h2>
          <p>Sem cartão, sem prazo de teste e sem letras miúdas. Você só migra para o plano pago se quiser mais agendamentos.</p>
        </div>

        <div class="freemium-grid">
          <div class="freemium-card destaque">
            <h3>Grátis</h3>
            <p class="sub" style="color:var(--muted);margin:0">Para começar a organizar a casa</p>
            <div class="preco">R$ 0 <small>/ sempre</small></div>
            <ul class="check">
              <li><?php echo htmlspecialchars($bbv_agendamentos_gratis); ?></li>
              <li><?php echo htmlspecialchars(bbv_limite($bbv_limite_prof_gratis, 'profissional', 'profissionais')); ?> no cadastro</li>
              <li>Site e painel completos, sem função bloqueada</li>
              <li>Clientes, serviços, produtos e financeiro</li>
              <li>Agendamentos pelo site, 24 horas por dia</li>
            </ul>
            <div class="nota">
              <strong>Como funciona o limite:</strong> ao chegar no total de agendamentos do mês,
              novos agendamentos ficam pausados até o mês seguinte. Nada é apagado — seus dados
              e seu site continuam no ar. Se quiser atender sem limite no mesmo mês, é só ativar o plano pago.
            </div>
          </div>

          <div class="freemium-card">
            <h3><?php echo $bbv_plano_pago ? htmlspecialchars($bbv_plano_pago['nome']) : 'PRO'; ?></h3>
            <p class="sub" style="color:var(--muted);margin:0">Para quem já tem movimento todos os dias</p>
            <div class="preco"><?php echo bbv_brl($bbv_preco_pago); ?> <small>/ mês</small></div>
            <ul class="check">
              <li>Agendamentos ilimitados</li>
              <li><?php echo $bbv_plano_pago ? htmlspecialchars(bbv_limite($bbv_plano_pago['limite_profissionais'], 'profissional', 'profissionais')) : 'Profissionais ilimitados'; ?></li>
              <li>Mesmos recursos do grátis, sem cota mensal</li>
              <li>Pagamento por Pix, boleto ou cartão no checkout</li>
              <li>Assinatura mensal, cancela quando quiser</li>
            </ul>
            <div class="nota">
              <strong>Sem fidelidade:</strong> a cobrança é mensal e o cancelamento pode ser feito a qualquer momento.
              Ao cancelar, a conta volta para o plano gratuito e os dados continuam salvos.
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="planos">
      <div class="wrap">
        <div class="section-head">
          <h2>Planos claros, sem surpresa</h2>
          <p>Escolha pelo volume de atendimento. Todos incluem o sistema completo.</p>
        </div>

        <div class="planos-grid">
          <article class="plano">
            <h3>Grátis</h3>
            <p class="sub">Para começar agora</p>
            <div class="valor">R$ 0 <small>/ sempre</small></div>
            <ul class="check">
              <li><?php echo htmlspecialchars($bbv_agendamentos_gratis); ?></li>
              <li><?php echo htmlspecialchars(bbv_limite($bbv_limite_prof_gratis, 'profissional', 'profissionais')); ?></li>
              <li>Site próprio incluído</li>
              <li>Painel completo</li>
              <li>Sem cartão de crédito</li>
            </ul>
            <a class="btn btn-ghost" href="assinar.php">Criar conta grátis</a>
          </article>

          <?php foreach ($bbv_pagos as $bbv_indice => $bbv_p) {
              $bbv_popular = ($bbv_indice === 0);
          ?>
            <article class="plano<?php echo $bbv_popular ? ' popular' : ''; ?>">
              <?php if ($bbv_popular) { ?><span class="tag">Mais escolhido</span><?php } ?>
              <h3><?php echo htmlspecialchars($bbv_p['nome']); ?></h3>
              <p class="sub"><?php echo $bbv_popular ? 'Para atender sem limite' : 'Mais recursos e limites'; ?></p>
              <div class="valor"><?php echo bbv_brl($bbv_p['valor']); ?> <small>/ mês</small></div>
              <ul class="check">
                <li><?php echo htmlspecialchars(bbv_limite($bbv_p['limite_agendamentos'], 'agendamento por mês', 'agendamentos por mês')); ?></li>
                <li><?php echo htmlspecialchars(bbv_limite($bbv_p['limite_profissionais'], 'profissional', 'profissionais')); ?></li>
                <li>Todos os recursos do sistema</li>
                <li>Suporte por WhatsApp</li>
                <li>Cancelamento a qualquer momento</li>
              </ul>
              <a class="btn btn-gold" href="assinar.php">Assinar <?php echo htmlspecialchars($bbv_p['nome']); ?></a>
            </article>
          <?php } ?>

          <?php if (count($bbv_pagos) === 0) { ?>
            <article class="plano popular">
              <span class="tag">Mais escolhido</span>
              <h3>PRO</h3>
              <p class="sub">Para atender sem limite</p>
              <div class="valor"><?php echo bbv_brl($bbv_preco_pago); ?> <small>/ mês</small></div>
              <ul class="check">
                <li>Agendamentos ilimitados</li>
                <li>Profissionais ilimitados</li>
                <li>Todos os recursos do sistema</li>
                <li>Suporte por WhatsApp</li>
                <li>Cancelamento a qualquer momento</li>
              </ul>
              <a class="btn btn-gold" href="assinar.php">Assinar PRO</a>
            </article>
          <?php } ?>
        </div>
      </div>
    </section>

    <section id="comparar" style="padding-top:0">
      <div class="wrap">
        <div class="section-head">
          <h2>Comparação rápida</h2>
          <p>O que muda entre o gratuito e os planos pagos.</p>
        </div>
        <div class="tabela-wrap">
          <table>
            <thead>
              <tr>
                <th>Recurso</th>
                <th>Grátis</th>
                <th><?php echo $bbv_plano_pago ? htmlspecialchars($bbv_plano_pago['nome']) : 'PRO'; ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Agendamentos por mês</td>
                <td><?php echo $bbv_limite_gratis !== null ? (int) $bbv_limite_gratis : 'Ilimitado'; ?></td>
                <td><?php echo $bbv_plano_pago && (int) $bbv_plano_pago['limite_agendamentos'] > 0 ? (int) $bbv_plano_pago['limite_agendamentos'] : 'Ilimitado'; ?></td>
              </tr>
              <tr>
                <td>Profissionais</td>
                <td><?php echo $bbv_limite_prof_gratis !== null ? (int) $bbv_limite_prof_gratis : 'Ilimitado'; ?></td>
                <td><?php echo $bbv_plano_pago && (int) $bbv_plano_pago['limite_profissionais'] > 0 ? (int) $bbv_plano_pago['limite_profissionais'] : 'Ilimitado'; ?></td>
              </tr>
              <tr><td>Site próprio com agendamento</td><td class="yes">Incluído</td><td class="yes">Incluído</td></tr>
              <tr><td>Agenda, clientes e serviços</td><td class="yes">Incluído</td><td class="yes">Incluído</td></tr>
              <tr><td>Produtos, estoque e financeiro</td><td class="yes">Incluído</td><td class="yes">Incluído</td></tr>
              <tr><td>Comissões e relatórios em PDF</td><td class="yes">Incluído</td><td class="yes">Incluído</td></tr>
              <tr><td>Cartão de fidelidade</td><td class="yes">Incluído</td><td class="yes">Incluído</td></tr>
              <tr><td>Cartão de crédito no cadastro</td><td class="no">Não precisa</td><td class="no">Opcional no checkout</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section id="duvidas">
      <div class="wrap" style="max-width:820px">
        <div class="section-head">
          <h2>Dúvidas frequentes</h2>
        </div>

        <details>
          <summary>O plano gratuito expira?</summary>
          <p>Não. Ele é gratuito enquanto você quiser, com <?php echo htmlspecialchars($bbv_agendamentos_gratis); ?>.
             Não pedimos cartão e não existe prazo de teste.</p>
        </details>
        <details>
          <summary>O que acontece quando eu atinjo o limite do plano grátis?</summary>
          <p>Os agendamentos do mês ficam pausados até o próximo mês. O site, o painel e todos os
             dados continuam funcionando. Se precisar atender mais no mesmo mês, ative o plano pago
             no próprio painel.</p>
        </details>
        <details>
          <summary>Preciso instalar algo?</summary>
          <p>Não. O sistema e o site rodam no navegador, no computador e no celular. Você recebe um
             endereço próprio do estabelecimento e pode usar também um domínio seu.</p>
        </details>
        <details>
          <summary>Como funciona o pagamento do plano pago?</summary>
          <p>A assinatura é mensal, emitida no checkout com Pix, boleto ou cartão de crédito. O acesso
             é liberado automaticamente após a confirmação do pagamento.</p>
        </details>
        <details>
          <summary>Posso cancelar?</summary>
          <p>Sim, a qualquer momento. Ao cancelar, a conta volta para o plano gratuito e seus dados
             permanecem salvos.</p>
        </details>
        <details>
          <summary>Serve para salão de beleza também?</summary>
          <p>Sim. O sistema tem tema próprio para salão, com serviços, profissionais e textos do
             segmento.</p>
        </details>
      </div>
    </section>

    <section style="padding-top:0">
      <div class="wrap">
        <div class="cta-final">
          <h2>Comece grátis hoje</h2>
          <p>Crie a conta, escolha o endereço do seu site e comece a receber agendamentos. Leva menos de um minuto.</p>
          <a class="btn btn-gold" href="assinar.php">Criar minha conta grátis</a>
          <p style="margin:16px 0 0;font-size:13px">
            Já tem conta? <a href="sistema/">Entrar no painel</a>
          </p>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="wrap foot-inner">
      <span>Barbearia PRO · sistema para barbearias e salões</span>
      <span class="foot-links">
        <a href="assinar.php">Criar conta</a>
        <a href="sistema/">Entrar</a>
        <a href="./">Voltar ao site</a>
      </span>
    </div>
  </footer>

</body>
</html>
