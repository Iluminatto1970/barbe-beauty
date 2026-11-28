<?php
// ============================================================
// PAGINA DE PAGAMENTO DO AUTOCADASTRO
// Mostra o link do checkout do Asaas e atualiza sozinha quando
// o webhook confirma o pagamento.
// ============================================================
require_once(__DIR__ . '/superadmin/_auth.php');
require_once(__DIR__ . '/superadmin/_asaas.php');
require_once(__DIR__ . '/sistema/email.php');
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);
bb_tenant_garantir_colunas($pdo);

$token = isset($_GET['t']) ? preg_replace('/[^a-f0-9]/i', '', (string) $_GET['t']) : '';
if ($token === '') {
    header('Location: assinar.php');
    exit;
}

$q = $pdo->prepare("SELECT t.*, p.nome AS plano, p.valor AS plano_valor FROM tenants t
    LEFT JOIN saas_planos p ON p.id = t.plano_id
    WHERE t.token = :token LIMIT 1");
$q->bindValue(':token', $token);
$q->execute();
$tenant = $q->fetch(PDO::FETCH_ASSOC);

if (!$tenant) {
    http_response_code(404);
    echo 'Cadastro não encontrado.';
    exit;
}

$gratis = ((float) $tenant['plano_valor'] <= 0);
$pago = in_array($tenant['status'], array('ativo', 'trial'), true);
$pagamento = null;
$erro_api = '';
$valor = ((float) $tenant['plano_valor'] > 0) ? (float) $tenant['plano_valor'] : asaas_config()['valor'];

// Cobranca pendente: confere a API e libera na hora. O webhook pode ter
// atrasado (ou nem estar cadastrado), e o cliente nao pode ficar preso.
if (!$pago && !$gratis && asaas_configurado()) {
    if (empty($tenant['asaas_subscription_id'])) {
        // Assinatura nao foi criada no cadastro: gera agora
        $criada = asaas_criar_assinatura($pdo, $tenant, $valor, 'UNDEFINED', date('Y-m-d'));
        if ($criada['ok']) {
            $q = $pdo->prepare("SELECT t.*, p.nome AS plano, p.valor AS plano_valor FROM tenants t
                LEFT JOIN saas_planos p ON p.id = t.plano_id
                WHERE t.token = :token LIMIT 1");
            $q->bindValue(':token', $token);
            $q->execute();
            $tenant = $q->fetch(PDO::FETCH_ASSOC);
            if (!empty($tenant['asaas_subscription_id'])) {
                $valor = ((float) $tenant['plano_valor'] > 0) ? (float) $tenant['plano_valor'] : $valor;
            }
        } else {
            $erro_api = $criada['erro'];
        }
    }

    if (!empty($tenant['asaas_subscription_id'])) {
        $r = asaas_req('GET', '/subscriptions/' . $tenant['asaas_subscription_id'] . '/payments?limit=1');
        if ($r['ok'] && !empty($r['dados']['data'][0])) {
            $pagamento = $r['dados']['data'][0];
            if (asaas_status_pago(isset($pagamento['status']) ? $pagamento['status'] : '')) {
                $evento = (strtoupper((string) $pagamento['status']) === 'CONFIRMED')
                    ? 'PAYMENT_CONFIRMED' : 'PAYMENT_RECEIVED';
                asaas_liberar_tenant($pdo, $tenant, $pagamento, $evento);
                header('Location: assinar-pagamento.php?t=' . urlencode($token));
                exit;
            }
        } elseif (!$r['ok']) {
            $erro_api = $r['erro'];
        }
    }
}
$boas_vindas_enviado = !empty($tenant['boas_vindas_em']);
$painel_url = bb_boas_vindas_url($tenant['slug']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pagamento · <?php echo sa_e($tenant['nome']); ?></title>
  <link rel="stylesheet" href="superadmin/css/superadmin.css">
  <style>
    .pagina { max-width: 620px; margin: 0 auto; padding: 34px 18px 50px; }
    .cabeca { text-align: center; margin-bottom: 20px; }
    .cabeca h1 { font-size: 24px; }
    .cabeca p { color: var(--muted); margin: 8px 0 0; }
    .resumo { display: grid; gap: 8px; margin: 16px 0 0; }
    .linha { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; }
    .linha span:first-child { color: var(--muted); }
    .total { border-top: 1px solid var(--line); margin-top: 10px; padding-top: 10px; font-size: 18px; font-weight: 700; }
    .acoes { display: grid; gap: 10px; margin-top: 18px; }
    .btn { text-align: center; }
    .ok-icone { font-size: 40px; text-align: center; }
    .passos { margin-top: 18px; font-size: 13px; color: var(--muted); line-height: 1.6; }
    .passos strong { color: var(--text); }
  </style>
</head>
<body>
  <div class="pagina">
    <?php if ($pago) { ?>
      <div class="ok-icone">✅</div>
      <div class="cabeca">
        <h1><?php echo $gratis ? 'Conta criada' : 'Pagamento confirmado'; ?></h1>
        <p>Sua conta <strong><?php echo sa_e($tenant['nome']); ?></strong> está ativa<?php echo $gratis ? ' no plano gratuito' : ''; ?>.</p>
      </div>
      <div class="painel">
        <div class="resumo">
          <div class="linha"><span>Plano</span><span><?php echo sa_e($tenant['plano'] ? $tenant['plano'] : 'Mensal'); ?></span></div>
          <?php if ($gratis) { ?>
            <div class="linha"><span>Mensalidade</span><span>Grátis</span></div>
            <div class="linha"><span>Limite do plano</span><span><?php echo (int) bb_quota_plano()['limite']; ?> agendamentos/mês</span></div>
            <div class="linha"><span>Plano ilimitado</span><span><?php echo sa_moeda(bb_plano_upgrade_preco()); ?>/mês</span></div>
          <?php } else { ?>
            <div class="linha"><span>Mensalidade</span><span><?php echo sa_moeda($valor); ?></span></div>
            <div class="linha"><span>Próximo vencimento</span><span><?php echo sa_data($tenant['vencimento']); ?></span></div>
          <?php } ?>
          <div class="linha"><span>Endereço do site</span><span><?php echo sa_e($tenant['slug'] . '.' . SA_DOMINIO_BASE); ?></span></div>
        </div>
      </div>
      <div class="painel">
        <?php
        // Sem transporte de e-mail configurado (RESEND_API_KEY) a promessa de
        // "enviaremos as instruções" seria falsa: mostra o caminho real.
        $bb_email_cfg = bb_email_config();
        $bb_email_pronto = ($bb_email_cfg['transporte'] === 'log') || ($bb_email_cfg['resend'] !== '');
        ?>
        <p style="margin:0 0 10px">Pagamento identificado para <strong><?php echo sa_e($tenant['email']); ?></strong>.
          <?php if ($boas_vindas_enviado) { ?>
            Enviamos as instruções de acesso para este e-mail (confira também a caixa de spam).
          <?php } elseif ($bb_email_pronto) { ?>
            A liberação está sendo concluída e as instruções de acesso serão enviadas para este e-mail.
          <?php } else { ?>
            Seu acesso está liberado. Guarde o link do painel abaixo e use a senha que você criou no cadastro.
          <?php } ?>
        </p>
        <p style="margin:0;font-size:14px">Painel do estabelecimento:
          <a href="<?php echo sa_e($painel_url); ?>"><?php echo sa_e($painel_url); ?></a></p>
      </div>
      <div class="acoes">
        <a class="btn primario" href="<?php echo sa_e($painel_url); ?>">Acessar meu painel</a>
        <a class="btn" href="assinar-pagamento.php?t=<?php echo sa_e($token); ?>">Atualizar</a>
      </div>
    <?php } else { ?>
      <div class="cabeca">
        <h1>Falta só o pagamento</h1>
        <p>Cadastro de <strong><?php echo sa_e($tenant['nome']); ?></strong> criado. Conclua a primeira mensalidade para liberar o acesso.</p>
      </div>

      <div class="painel">
        <div class="resumo">
          <div class="linha"><span>Plano</span><span><?php echo sa_e($tenant['plano'] ? $tenant['plano'] : 'Mensal'); ?></span></div>
          <div class="linha"><span>Endereço do site</span><span><?php echo sa_e($tenant['slug'] . '.' . SA_DOMINIO_BASE); ?></span></div>
          <?php if ($pagamento) { ?>
            <div class="linha"><span>Vencimento</span><span><?php echo sa_data($pagamento['dueDate']); ?></span></div>
          <?php } ?>
          <div class="linha total"><span>Total</span><span><?php echo sa_moeda($valor); ?></span></div>
        </div>

        <?php if ($erro_api !== '') { ?>
          <div class="aviso erro" style="margin-bottom:0">Não foi possível consultar o Asaas agora: <?php echo sa_e($erro_api); ?></div>
        <?php } ?>

        <div class="acoes">
          <?php if ($pagamento && !empty($pagamento['invoiceUrl'])) { ?>
            <a class="btn primario" href="<?php echo sa_e($pagamento['invoiceUrl']); ?>" target="_blank" rel="noopener">Abrir página de pagamento</a>
            <button type="button" class="btn" id="btn-copiar" data-link="<?php echo sa_e($pagamento['invoiceUrl']); ?>">Copiar link do pagamento</button>
          <?php } else { ?>
            <p class="muted" style="margin:0">Gerando a cobrança… atualize esta página em alguns instantes.</p>
            <a class="btn" href="assinar-pagamento.php?t=<?php echo sa_e($token); ?>">Atualizar</a>
          <?php } ?>
        </div>
      </div>

      <div class="passos">
        <p><strong>1.</strong> Abra a página de pagamento e escolha Pix, boleto ou cartão.</p>
        <p><strong>2.</strong> Esta tela é atualizada automaticamente assim que o Asaas confirmar.</p>
        <p><strong>3.</strong> A mensalidade seguinte é gerada automaticamente todo mês.</p>
      </div>
    <?php } ?>
  </div>

  <script>
    (function () {
      var copiar = document.getElementById('btn-copiar');
      if (copiar) {
        copiar.addEventListener('click', function () {
          var link = this.getAttribute('data-link');
          var texto = this;
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(link).then(function () {
              texto.textContent = 'Link copiado!';
              setTimeout(function () { texto.textContent = 'Copiar link do pagamento'; }, 2000);
            });
          } else {
            window.prompt('Copie o link do pagamento:', link);
          }
        });
      }

      <?php if (!$pago) { ?>
      var tentativas = 0;
      var timer = setInterval(function () {
        tentativas++;
        if (tentativas > 90) { clearInterval(timer); return; }
        fetch('assinar-status.php?t=<?php echo sa_e($token); ?>', { cache: 'no-store' })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (d && d.pago) { window.location.reload(); }
          })
          .catch(function () {});
      }, 10000);
      <?php } ?>
    })();
  </script>
</body>
</html>
