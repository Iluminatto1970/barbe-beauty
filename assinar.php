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

// Plano sugerido: o de R$ 49 (valor padrao) ou o mais barato
$plano_sugerido = 0;
foreach ($planos as $p) {
    if (abs((float) $p['valor'] - $cfg['valor']) < 0.01) {
        $plano_sugerido = (int) $p['id'];
        break;
    }
}
if ($plano_sugerido === 0 && count($planos) > 0) {
    $plano_sugerido = (int) $planos[0]['id'];
}

$erro = isset($_GET['erro']) ? (string) $_GET['erro'] : '';
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
    .duas-colunas { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 560px) { .duas-colunas { grid-template-columns: 1fr; } }
    .rodape-assinar { text-align: center; color: var(--muted); font-size: 12px; padding: 26px 20px 40px; }
    .rodape-assinar a { color: var(--muted); }
  </style>
</head>
<body>
  <div class="assinar-hero">
    <div class="brand" style="margin-bottom:14px">⚙️ <strong>Barbearia PRO</strong><span class="muted">assinatura do sistema</span></div>
    <h1>Crie sua conta e comece a usar</h1>
    <p>Cadastre seu estabelecimento, escolha o plano e pague a primeira mensalidade. A cobrança se renova automaticamente todo mês.</p>
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
              <input type="text" id="slug" name="slug" required maxlength="40" pattern="[a-z0-9\-]+" placeholder="barbearia-estilo" value="<?php echo sa_e(isset($_GET['slug']) ? $_GET['slug'] : ''); ?>">
              <small class="muted" id="slug-dica">barbearia-estilo.<?php echo sa_e(SA_DOMINIO_BASE); ?></small>
            </div>
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
                  <span class="plano-preco"><?php echo sa_moeda($p['valor']); ?><span>/mês</span></span>
                </span>
              </label>
            <?php } ?>
          </div>

          <div class="acoes-form" style="margin-top:16px">
            <button type="submit" class="btn primario" style="width:100%">Criar conta e ir para o pagamento</button>
          </div>
          <p class="nota">Ao criar a conta você concorda com a cobrança mensal do plano escolhido. O cancelamento pode ser feito a qualquer momento.</p>
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
      var nome = document.getElementById('nome');
      var slug = document.getElementById('slug');
      var dica = document.getElementById('slug-dica');
      var base = <?php echo json_encode(SA_DOMINIO_BASE); ?>;
      var editou = false;

      function limpar(texto) {
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40);
      }

      if (!slug || !nome) { return; }

      slug.addEventListener('input', function () {
        editou = true;
        this.value = limpar(this.value);
        dica.textContent = (this.value || 'seu-endereco') + '.' + base;
      });

      nome.addEventListener('input', function () {
        if (editou) { return; }
        slug.value = limpar(this.value);
        dica.textContent = (slug.value || 'seu-endereco') + '.' + base;
      });
    })();
  </script>
</body>
</html>
