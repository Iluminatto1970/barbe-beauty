<?php
// ============================================================
// AUTOCADASTRO DO SAAS
// O proprio cliente cria o estabelecimento, escolhe o plano e
// assina a cobranca mensal (Pix, boleto ou cartao no checkout).
// ============================================================
require_once(__DIR__ . '/superadmin/_auth.php');
require_once(__DIR__ . '/superadmin/_asaas.php');
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);

$planos = $pdo->query("SELECT * FROM saas_planos WHERE ativo = 'Sim' ORDER BY valor ASC")->fetchAll(PDO::FETCH_ASSOC);
$cfg = asaas_config();

// Plano sugerido: o gratuito (menor valor)
$plano_sugerido = 0;
$menor_valor = null;
foreach ($planos as $p) {
    if ($menor_valor === null || (float) $p['valor'] < $menor_valor) {
        $plano_sugerido = (int) $p['id'];
        $menor_valor = (float) $p['valor'];
    }
}

$erro = isset($_GET['erro']) ? (string) $_GET['erro'] : '';
$seg_sel = (isset($_GET['segmento']) && $_GET['segmento'] === 'salao') ? 'salao' : 'barbearia';
$csrf = bin2hex(random_bytes(16));
$_SESSION['assinar_csrf'] = $csrf;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Criar minha conta · Barbearia PRO</title>
  <link rel="stylesheet" href="superadmin/css/superadmin.css">
  <style>
    .assinar-hero { max-width: 1180px; margin: 0 auto; padding: 34px 20px 6px; }
    .assinar-hero h1 { font-size: 28px; line-height: 1.2; }
    .assinar-hero p { color: var(--muted); margin: 8px 0 0; max-width: 620px; }
    .assinar-grade { display: grid; grid-template-columns: 1.15fr .85fr; gap: 18px; align-items: start; }
    @media (max-width: 900px) { .assinar-grade { grid-template-columns: 1fr; } }
    .planos { display: grid; gap: 10px; }
    .plano-opcao { position: relative; display: block; cursor: pointer; }
    .plano-opcao input { position: absolute; opacity: 0; pointer-events: none; }
    .plano-card {
      border: 1px solid var(--line); background: var(--panel-2); border-radius: 12px;
      padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px;
      transition: border-color .2s, transform .2s;
    }
    .plano-opcao:hover .plano-card { border-color: var(--accent); }
    .plano-opcao input:checked + .plano-card { border-color: var(--accent); box-shadow: inset 0 0 0 1px var(--accent); }
    .plano-card strong { display: block; font-size: 15px; }
    .plano-card small { color: var(--muted); font-size: 12px; }
    .plano-preco { font-size: 20px; font-weight: 700; color: var(--accent); white-space: nowrap; }
    .plano-preco span { font-size: 12px; font-weight: 400; color: var(--muted); }
    .formas { display: flex; gap: 8px; flex-wrap: wrap; }
    .formas label {
      display: inline-flex; align-items: center; gap: 6px; padding: 9px 12px;
      border: 1px solid var(--line); border-radius: 9px; background: var(--panel-2);
      font-size: 13px; cursor: pointer;
    }
    .formas input { accent-color: var(--accent); }
    .nota { font-size: 12px; color: var(--muted); margin-top: 10px; }
    .slug-status { display: block; margin-top: 6px; font-size: 12px; line-height: 1.4; color: var(--muted); }
    .slug-status[data-estado="livre"] { color: var(--ok); }
    .slug-status[data-estado="reservado"], .slug-status[data-estado="em_uso"] { color: var(--danger); }
    .slug-status[data-estado="curto"], .slug-status[data-estado="longo"], .slug-status[data-estado="formato"] { color: var(--warn); }
    .duas-colunas { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 560px) { .duas-colunas { grid-template-columns: 1fr; } }
    .rodape-assinar { text-align: center; color: var(--muted); font-size: 12px; padding: 26px 20px 40px; }
    .rodape-assinar a { color: var(--muted); }
  </style>
</head>
<body>
  <div class="assinar-hero">
    <div class="brand" style="margin-bottom:14px">⚙️ <strong>Barbearia PRO</strong><span class="muted">assinatura do sistema</span></div>
    <h1>Crie sua conta e comece grátis</h1>
    <p>Para barbearias, salões de beleza e qualquer estabelecimento que trabalhe com beleza. Comece no plano gratuito com até 20 agendamentos por mês — quando precisar de mais, o plano PRO libera agendamentos ilimitados por R$ 49/mês, com agenda, clientes, estoque, financeiro e relatórios no mesmo sistema.</p>
  </div>

  <div class="wrap" style="padding-top:18px">
    <?php if ($erro !== '') { ?>
      <div class="aviso erro"><?php echo sa_e($erro); ?></div>
    <?php } ?>

    <form method="post" action="assinar-salvar.php">
      <input type="hidden" name="csrf" value="<?php echo sa_e($csrf); ?>">

      <div class="assinar-grade">
        <div class="painel" style="margin-top:0">
          <h2 style="margin-top:0">Seus dados</h2>

          <div class="duas-colunas">
            <div class="campo">
              <label for="nome">Nome do estabelecimento</label>
              <input type="text" id="nome" name="nome" required maxlength="100" placeholder="Barbearia Estilo" value="<?php echo sa_e(isset($_GET['nome']) ? $_GET['nome'] : ''); ?>">
            </div>
            <div class="campo">
              <label for="slug">Endereço do seu site</label>
              <input type="text" id="slug" name="slug" required maxlength="40" pattern="[a-z0-9\-]+" placeholder="barbearia-estilo" aria-describedby="slug-dica slug-status" value="<?php echo sa_e(isset($_GET['slug']) ? $_GET['slug'] : ''); ?>">
              <small class="muted" id="slug-dica">barbearia-estilo.<?php echo sa_e(SA_DOMINIO_BASE); ?></small>
              <small class="slug-status" id="slug-status" role="status" aria-live="polite"></small>
            </div>
          </div>

          <div class="campo" style="margin-top:12px">
            <label>Seu negócio é</label>
            <div class="formas">
              <label><input type="radio" name="segmento" value="barbearia" <?php echo $seg_sel === 'barbearia' ? 'checked' : ''; ?>> Barbearia</label>
              <label><input type="radio" name="segmento" value="salao" <?php echo $seg_sel === 'salao' ? 'checked' : ''; ?>> Salão de beleza / estética</label>
            </div>
            <small class="muted">Aplica o tema no site, no agendamento, no login e no painel. Dá para trocar depois em Configurações.</small>
          </div>

          <div class="duas-colunas" style="margin-top:12px">
            <div class="campo">
              <label for="email">E-mail</label>
              <input type="email" id="email" name="email" required maxlength="120" placeholder="voce@email.com" value="<?php echo sa_e(isset($_GET['email']) ? $_GET['email'] : ''); ?>">
            </div>
            <div class="campo">
              <label for="telefone">WhatsApp</label>
              <input type="text" id="telefone" name="telefone" maxlength="20" placeholder="(11) 99999-9999" value="<?php echo sa_e(isset($_GET['telefone']) ? $_GET['telefone'] : ''); ?>">
            </div>
          </div>

          <div class="campo" style="margin-top:12px">
            <label for="cpf_cnpj">CPF ou CNPJ</label>
            <input type="text" id="cpf_cnpj" name="cpf_cnpj" maxlength="20" placeholder="somente números" value="<?php echo sa_e(isset($_GET['cpf_cnpj']) ? $_GET['cpf_cnpj'] : ''); ?>">
            <small class="muted">Necessário apenas para emitir a cobrança do plano pago.</small>
          </div>

          <div class="duas-colunas" style="margin-top:12px">
            <div class="campo">
              <label for="senha">Senha de acesso</label>
              <input type="password" id="senha" name="senha" required minlength="6" placeholder="mínimo 6 caracteres">
            </div>
            <div class="campo">
              <label for="senha2">Confirmar senha</label>
              <input type="password" id="senha2" name="senha2" required minlength="6" placeholder="repita a senha">
            </div>
          </div>

          <h2>Forma de pagamento</h2>
          <div class="formas">
            <label><input type="radio" name="forma" value="UNDEFINED" checked> Escolher no checkout</label>
            <label><input type="radio" name="forma" value="PIX"> Pix</label>
            <label><input type="radio" name="forma" value="BOLETO"> Boleto</label>
          </div>
          <p class="nota">No checkout do Asaas o cliente também pode pagar com cartão de crédito.</p>
        </div>

        <div class="painel" style="margin-top:0">
          <h2 style="margin-top:0">Escolha o plano</h2>
          <div class="planos">
            <?php if (count($planos) == 0) { ?>
              <p class="muted">Nenhum plano disponível no momento.</p>
            <?php } ?>
            <?php foreach ($planos as $p) { ?>
              <label class="plano-opcao">
                <input type="radio" name="plano_id" value="<?php echo (int) $p['id']; ?>" <?php echo ((int) $p['id'] === $plano_sugerido) ? 'checked' : ''; ?>>
                <span class="plano-card">
                  <span>
                    <strong><?php echo sa_e($p['nome']); ?></strong>
                    <small>
                      <?php echo (int) $p['limite_profissionais'] > 0 ? (int) $p['limite_profissionais'] . ' profissionais' : 'Profissionais ilimitados'; ?>
                      ·
                      <?php echo (int) $p['limite_agendamentos'] > 0 ? (int) $p['limite_agendamentos'] . ' agendamentos/mês' : 'Agendamentos ilimitados'; ?>
                    </small>
                  </span>
                  <span class="plano-preco"><?php echo ((float) $p['valor'] <= 0) ? 'Grátis' : sa_moeda($p['valor']); ?><span><?php echo ((float) $p['valor'] <= 0) ? '' : '/mês'; ?></span></span>
                </span>
              </label>
            <?php } ?>
          </div>

          <div class="acoes-form" style="margin-top:16px">
            <button type="submit" class="btn primario" style="width:100%">Criar minha conta</button>
          </div>
          <p class="nota">O plano gratuito não pede cartão. No plano pago a cobrança é mensal e o cancelamento pode ser feito a qualquer momento.</p>
          <p class="nota"><a href="planos.php">Ver todos os recursos e comparar os planos</a></p>
          <p class="nota">Já tem conta? <a href="sistema/">Entrar no painel</a></p>
        </div>
      </div>
    </form>
  </div>

  <div class="rodape-assinar">
    Barbearia PRO · <a href="index">voltar ao site</a>
  </div>

  <script>
    (function () {
      var form = document.querySelector('form[action="assinar-salvar.php"]');
      var nome = document.getElementById('nome');
      var slug = document.getElementById('slug');
      var dica = document.getElementById('slug-dica');
      var status = document.getElementById('slug-status');
      var base = <?php echo json_encode(SA_DOMINIO_BASE); ?>;
      var editou = false;
      var timer = null;
      var pedido = 0;
      var estado = '';
      var conferido = '';
      var controlador = null;

      function limpar(texto) {
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40);
      }

      if (!slug || !nome) { return; }

      function pintar(novo, texto) {
        estado = novo;
        if (!status) { return; }
        status.setAttribute('data-estado', novo);
        status.textContent = texto || '';
      }

      // Mesma regra do servidor: quem decide livre/reservado/em uso e bb_slug_avaliar
      function conferir(valor) {
        if (valor.length < 3) {
          conferido = valor;
          pintar(valor === '' ? '' : 'curto', valor === '' ? '' : 'O endereço precisa ter ao menos 3 caracteres.');
          return;
        }

        pedido += 1;
        var meu = pedido;
        if (controlador) { controlador.abort(); }
        controlador = (typeof AbortController !== 'undefined') ? new AbortController() : null;
        pintar('conferindo', 'Verificando se está livre…');

        fetch('assinar-slug.php?slug=' + encodeURIComponent(valor), {
          cache: 'no-store',
          signal: controlador ? controlador.signal : undefined
        })
          .then(function (r) {
            if (!r.ok) { throw new Error('http ' + r.status); }
            return r.json();
          })
          .then(function (dados) {
            if (meu !== pedido || slug.value !== valor) { return; }
            conferido = valor;
            var host = dados.host || (valor + '.' + base);
            if (dica) { dica.textContent = host; }
            if (dados.estado === 'livre') {
              pintar('livre', '✓ ' + host + ' está livre.');
            } else {
              pintar(dados.estado, dados.motivo || 'Este endereço não pode ser usado.');
            }
          })
          .catch(function (erro) {
            if (erro && erro.name === 'AbortError') { return; }
            if (meu !== pedido) { return; }
            conferido = '';
            pintar('', 'Não foi possível verificar agora. O endereço será validado ao enviar.');
          });
      }

      function agendar(valor) {
        clearTimeout(timer);
        timer = setTimeout(function () { conferir(valor); }, 350);
      }

      slug.addEventListener('input', function () {
        editou = true;
        this.value = limpar(this.value);
        if (dica) { dica.textContent = (this.value || 'seu-endereco') + '.' + base; }
        agendar(this.value);
      });

      nome.addEventListener('input', function () {
        if (editou) { return; }
        slug.value = limpar(this.value);
        if (dica) { dica.textContent = (slug.value || 'seu-endereco') + '.' + base; }
        agendar(slug.value);
      });

      // Nao deixa enviar com um problema que o servidor ja apontou
      if (form) {
        form.addEventListener('submit', function (evento) {
          var bloqueios = ['curto', 'longo', 'formato', 'reservado', 'em_uso'];
          if (slug.value !== '' && conferido === slug.value && bloqueios.indexOf(estado) !== -1) {
            evento.preventDefault();
            slug.focus();
          }
        }, true);
      }

      if (slug.value) { agendar(slug.value); }
    })();
  </script>
</body>
</html>
