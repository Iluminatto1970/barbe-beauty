<?php
// ============================================================
// ATIVAR PLANO PRO (estabelecimento logado)
// Cria a assinatura recorrente no Asaas e envia o cliente para
// o primeiro pagamento. A liberacao e automatica pelo webhook.
// ============================================================
require_once(__DIR__ . '/verificar.php');
require_once(__DIR__ . '/../../superadmin/_asaas.php');

$quota = bb_quota_plano($pdo);
$preco = (float) $quota['preco'];
$erro = '';
$link_pagamento = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bb_csrf_check_request();

    if (empty($tenant_atual) || empty($tenant_atual['id'])) {
        $erro = 'Estabelecimento não identificado.';
    } elseif (!asaas_configurado()) {
        $erro = 'Pagamento indisponível no momento. Fale com o suporte.';
    } else {
        $forma = isset($_POST['forma']) ? (string) $_POST['forma'] : 'UNDEFINED';
        if (!in_array($forma, array('UNDEFINED', 'PIX', 'BOLETO', 'CREDIT_CARD'), true)) {
            $forma = 'UNDEFINED';
        }

        $tenant = $tenant_atual;
        $r = asaas_criar_assinatura($pdo_controle, $tenant, $preco, $forma, date('Y-m-d'));
        if (!$r['ok']) {
            $erro = $r['erro'];
        } else {
            // Registra o plano contratado (a cota so libera com o pagamento)
            $plano_id = bb_plano_pago_id($pdo_controle);
            if ($plano_id > 0) {
                try {
                    $q = $pdo_controle->prepare("UPDATE tenants SET plano_id = :plano WHERE id = :id");
                    $q->bindValue(':plano', $plano_id, PDO::PARAM_INT);
                    $q->bindValue(':id', (int) $tenant['id'], PDO::PARAM_INT);
                    $q->execute();
                } catch (Exception $e) {
                    // plano e apenas informativo; a assinatura ja foi criada
                }
            }

            // Link do primeiro pagamento da assinatura
            $rp = asaas_req('GET', '/subscriptions/' . $r['id'] . '/payments?limit=1');
            if ($rp['ok'] && !empty($rp['dados']['data'][0]['invoiceUrl'])) {
                $link_pagamento = (string) $rp['dados']['data'][0]['invoiceUrl'];
            }
        }
    }
}

$valor_fmt = number_format($preco, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ativar plano PRO</title>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: #eef2f6; color: #25313d; }
    .topo { background: #1b2733; color: #fff; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; }
    .topo a { color: #cfe0ec; text-decoration: none; font-size: 14px; }
    .wrap { max-width: 640px; margin: 28px auto; padding: 0 16px; }
    .card { background: #fff; border: 1px solid #dbe3ea; border-radius: 12px; padding: 22px; }
    h1 { font-size: 22px; margin: 0 0 8px; }
    p { line-height: 1.6; font-size: 14px; color: #4a5765; }
    .uso { background: #f7fafc; border: 1px solid #dbe3ea; border-radius: 10px; padding: 12px 14px; font-size: 14px; margin: 14px 0 18px; }
    .forma { display: flex; gap: 10px; flex-wrap: wrap; margin: 8px 0 18px; }
    .forma label { border: 1px solid #dbe3ea; border-radius: 8px; padding: 10px 14px; cursor: pointer; font-size: 14px; }
    button { background: #1b2733; color: #fff; border: 0; border-radius: 8px; padding: 13px 22px; font-size: 15px; font-weight: 600; cursor: pointer; width: 100%; }
    .erro { background: #fdecea; border: 1px solid #f5c2c0; color: #96251f; border-radius: 8px; padding: 12px 14px; font-size: 14px; margin-bottom: 14px; }
    .ok { background: #e9f7ef; border: 1px solid #b7e4c7; color: #1e6b3a; border-radius: 8px; padding: 12px 14px; font-size: 14px; margin-bottom: 14px; }
    .ok a { color: #1e6b3a; font-weight: 700; }
    ul { padding-left: 18px; font-size: 14px; color: #4a5765; line-height: 1.8; }
  </style>
</head>
<body>
  <div class="topo">
    <strong>Ativar plano PRO</strong>
    <a href="index.php">&larr; Voltar ao painel</a>
  </div>
  <div class="wrap">
    <div class="card">
      <?php if ($link_pagamento !== '') { ?>
        <div class="ok">
          Assinatura criada! Finalize o primeiro pagamento:
          <a href="<?php echo bb_e($link_pagamento); ?>" target="_blank" rel="noopener">abrir pagamento</a>.
          O painel é liberado automaticamente após a confirmação.
        </div>
      <?php } ?>
      <?php if ($erro !== '') { ?>
        <div class="erro"><?php echo bb_e($erro); ?></div>
      <?php } ?>

      <h1>Plano PRO — R$ <?php echo $valor_fmt; ?>/mês</h1>
      <p>Agendamentos ilimitados e todos os recursos do gestor liberados para o seu estabelecimento.</p>

      <div class="uso">
        Situação atual: plano <strong><?php echo bb_e($quota['plano']); ?></strong> ·
        <strong><?php echo (int) $quota['usados']; ?>/<?php echo (int) $quota['limite']; ?></strong>
        agendamentos neste mês
      </div>

      <ul>
        <li>Agendamentos ilimitados, sem cota mensal</li>
        <li>Agenda online, clientes, serviços e profissionais</li>
        <li>Produtos/estoque, financeiro, comissões e relatórios</li>
        <li>Cobrança mensal automática · cancele quando quiser</li>
      </ul>

      <form method="post" action="plano.php">
        <input type="hidden" name="csrf_token" value="<?php echo bb_e(bb_csrf_token()); ?>">
        <p style="margin-bottom:6px"><strong>Forma de pagamento</strong></p>
        <div class="forma">
          <label><input type="radio" name="forma" value="PIX" checked> PIX</label>
          <label><input type="radio" name="forma" value="BOLETO"> Boleto</label>
          <label><input type="radio" name="forma" value="CREDIT_CARD"> Cartão</label>
        </div>
        <button type="submit">Ativar PRO por R$ <?php echo $valor_fmt; ?>/mês</button>
      </form>
      <p style="font-size:12px;color:#7c8b99;margin-bottom:0">A cobrança é recorrente e o acesso é liberado assim que o pagamento é confirmado.</p>
    </div>
  </div>
</body>
</html>
