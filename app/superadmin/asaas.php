<?php
require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/_asaas.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
asaas_garantir_colunas($pdo);

$cfg = asaas_config();
$teste = null;

if (isset($_GET['teste'])) {
    $teste = asaas_req('GET', '/finance/balance');
}

$lista = $pdo->query("SELECT t.*, p.nome AS plano, p.valor AS plano_valor
    FROM tenants t LEFT JOIN saas_planos p ON p.id = t.plano_id
    ORDER BY t.nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$url_webhook = 'https://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : SA_DOMINIO_BASE) . dirname($_SERVER['SCRIPT_NAME']) . '/webhook-asaas.php';
$url_webhook = str_replace('//webhook', '/webhook', $url_webhook);

sa_topo('Cobranças Asaas');
?>
    <h1>Cobranças Asaas</h1>
    <p class="sub">Assinatura mensal recorrente por estabelecimento, com baixa automática por webhook.</p>

    <?php if (isset($_GET['ok'])) { ?>
        <div class="aviso ok"><?php echo sa_e($_GET['ok']); ?></div>
    <?php } ?>
    <?php if (isset($_GET['erro'])) { ?>
        <div class="aviso erro"><?php echo sa_e($_GET['erro']); ?></div>
    <?php } ?>

    <div class="cards">
        <div class="card">
            <div class="rotulo">Chave da API</div>
            <div class="valor pequeno"><?php echo sa_e(asaas_chave_mascarada()); ?></div>
            <small class="muted">Ambiente: <?php echo $cfg['ambiente'] === 'sandbox' ? 'sandbox' : 'produção'; ?></small>
        </div>
        <div class="card">
            <div class="rotulo">Valor mensal padrão</div>
            <div class="valor pequeno"><?php echo sa_moeda($cfg['valor']); ?></div>
        </div>
        <div class="card">
            <div class="rotulo">Token do webhook</div>
            <div class="valor pequeno"><?php echo $cfg['webhook'] !== '' ? 'configurado' : 'pendente'; ?></div>
            <small class="muted">header asaas-access-token</small>
        </div>
    </div>

    <div class="painel">
        <h2 style="margin-top:0">Conexão</h2>
        <?php if ($teste !== null) { ?>
            <?php if ($teste['ok']) { ?>
                <div class="aviso ok">
                    API respondendo. Saldo em conta: <?php echo sa_moeda(isset($teste['dados']['balance']) ? $teste['dados']['balance'] : 0); ?>
                </div>
            <?php } else { ?>
                <div class="aviso erro">Falha ao falar com o Asaas: <?php echo sa_e($teste['erro']); ?></div>
            <?php } ?>
        <?php } ?>
        <p class="muted" style="margin-top:0">
            Webhook do Asaas: <code><?php echo sa_e($url_webhook); ?></code><br>
            Cadastre no painel do Asaas os eventos PAYMENT_RECEIVED, PAYMENT_CONFIRMED, PAYMENT_OVERDUE, PAYMENT_REFUNDED e SUBSCRIPTION_DELETED.
        </p>
        <a class="btn" href="asaas.php?teste=1">Testar conexão</a>
    </div>

    <h2>Assinaturas por estabelecimento</h2>
    <div class="painel">
        <?php if (count($lista) == 0) { ?>
            <p class="muted">Nenhum estabelecimento cadastrado.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>Plano</th>
                        <th>Mensalidade</th>
                        <th>Asaas</th>
                        <th>Vencimento</th>
                        <th class="dir">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $t) {
                    $valor_sugerido = ((float) $t['plano_valor'] > 0) ? (float) $t['plano_valor'] : $cfg['valor'];
                    $tem_assinatura = !empty($t['asaas_subscription_id']) && $t['asaas_assinatura'] !== 'cancelada';
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo sa_e($t['nome']); ?></strong><br>
                            <small class="muted"><?php echo sa_e($t['email'] ? $t['email'] : 'sem e-mail'); ?></small>
                        </td>
                        <td><?php echo sa_e($t['plano'] ? $t['plano'] : '—'); ?></td>
                        <td><?php echo sa_moeda($valor_sugerido); ?></td>
                        <td>
                            <?php if ($t['asaas_assinatura']) { ?>
                                <span class="badge <?php echo $t['asaas_assinatura'] === 'ativa' ? 'ok' : 'danger'; ?>"><?php echo sa_e($t['asaas_assinatura']); ?></span><br>
                                <small class="muted"><?php echo sa_e($t['asaas_subscription_id']); ?></small>
                            <?php } else { ?>
                                <span class="badge">sem assinatura</span>
                            <?php } ?>
                        </td>
                        <td class="agora"><?php echo sa_data($t['vencimento']); ?></td>
                        <td class="dir agora">
                            <?php if (!$tem_assinatura) { ?>
                                <form method="post" action="asaas-assinar.php" style="display:flex; gap:6px; justify-content:flex-end; align-items:center; flex-wrap:wrap;">
                                    <input type="hidden" name="tenant_id" value="<?php echo (int) $t['id']; ?>">
                                    <input type="number" name="valor" step="0.01" min="1" value="<?php echo sa_e(number_format($valor_sugerido, 2, '.', '')); ?>" style="width:100px; padding:6px 8px; border-radius:8px; border:1px solid var(--line); background:var(--panel-2); color:var(--text);">
                                    <select name="forma" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); background:var(--panel-2); color:var(--text);">
                                        <option value="UNDEFINED">Cliente escolhe</option>
                                        <option value="PIX">Pix</option>
                                        <option value="BOLETO">Boleto</option>
                                        <option value="CREDIT_CARD">Cartão</option>
                                    </select>
                                    <input type="date" name="vencimento" value="<?php echo date('Y-m-d'); ?>" style="padding:6px 8px; border-radius:8px; border:1px solid var(--line); background:var(--panel-2); color:var(--text);">
                                    <button type="submit" class="btn pequeno primario" <?php echo asaas_configurado() ? '' : 'disabled title="Configure a chave da API"'; ?>>Assinar</button>
                                </form>
                            <?php } else { ?>
                                <a class="btn pequeno perigo" href="asaas-cancelar.php?id=<?php echo (int) $t['id']; ?>" onclick="return confirm('Cancelar a assinatura recorrente no Asaas?');">Cancelar</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <p class="muted" style="margin-top:16px">
        Ao registrar o pagamento, o estabelecimento volta para <strong>ativo</strong> e o vencimento é prorrogado em 30 dias automaticamente.
    </p>
<?php
sa_rodape();
