<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);

$total      = (int) $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
$ativos     = (int) $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'ativo'")->fetchColumn();
$trial      = (int) $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'trial'")->fetchColumn();
$suspensos  = (int) $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'suspenso'")->fetchColumn();
$planosQtd  = (int) $pdo->query("SELECT COUNT(*) FROM saas_planos WHERE ativo = 'Sim'")->fetchColumn();

$mrr = (float) $pdo->query("SELECT COALESCE(SUM(p.valor),0) FROM tenants t JOIN saas_planos p ON p.id = t.plano_id WHERE t.status = 'ativo'")->fetchColumn();
$recebido = (float) $pdo->query("SELECT COALESCE(SUM(valor),0) FROM saas_pagamentos WHERE status = 'pago' AND DATE_FORMAT(pago_em, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetchColumn();

$vencendo = $pdo->query("SELECT t.*, p.nome AS plano, p.valor
    FROM tenants t LEFT JOIN saas_planos p ON p.id = t.plano_id
    WHERE t.status IN ('ativo','trial') AND t.vencimento IS NOT NULL
      AND t.vencimento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY t.vencimento ASC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

$pagamentos = $pdo->query("SELECT pg.*, t.nome AS tenant
    FROM saas_pagamentos pg LEFT JOIN tenants t ON t.id = pg.tenant_id
    ORDER BY pg.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

sa_topo('Dashboard');
?>
    <h1>Olá, <?php echo sa_e($_SESSION['superadmin_nome']); ?></h1>
    <p class="sub">Visão geral de estabelecimentos, planos e receita recorrente.</p>

    <div class="cards">
        <div class="card">
            <div class="rotulo">Estabelecimentos</div>
            <div class="valor"><?php echo $total; ?></div>
        </div>
        <div class="card">
            <div class="rotulo">Ativos</div>
            <div class="valor"><?php echo $ativos; ?></div>
        </div>
        <div class="card">
            <div class="rotulo">Em trial</div>
            <div class="valor"><?php echo $trial; ?></div>
        </div>
        <div class="card">
            <div class="rotulo">Suspensos</div>
            <div class="valor"><?php echo $suspensos; ?></div>
        </div>
        <div class="card">
            <div class="rotulo">MRR</div>
            <div class="valor pequeno"><?php echo sa_moeda($mrr); ?></div>
        </div>
        <div class="card">
            <div class="rotulo">Recebido no mês</div>
            <div class="valor pequeno"><?php echo sa_moeda($recebido); ?></div>
        </div>
    </div>

    <h2>Assinaturas vencendo em 7 dias</h2>
    <div class="painel">
        <?php if (count($vencendo) == 0) { ?>
            <p class="muted">Nenhuma assinatura vencendo nos próximos dias.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>Estabelecimento</th><th>Plano</th><th>Valor</th><th>Vencimento</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($vencendo as $t) { ?>
                    <tr>
                        <td><?php echo sa_e($t['nome']); ?></td>
                        <td><?php echo sa_e($t['plano'] ? $t['plano'] : '—'); ?></td>
                        <td><?php echo sa_moeda($t['valor']); ?></td>
                        <td class="agora"><?php echo sa_data($t['vencimento']); ?></td>
                        <td><?php echo sa_status_badge($t['status']); ?></td>
                        <td class="dir"><a class="btn pequeno" href="assinaturas.php">Renovar</a></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <h2>Últimos pagamentos</h2>
    <div class="painel">
        <?php if (count($pagamentos) == 0) { ?>
            <p class="muted">Nenhum pagamento registrado ainda.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>Estabelecimento</th><th>Referência</th><th>Valor</th><th>Status</th><th>Pago em</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pagamentos as $pg) { ?>
                    <tr>
                        <td><?php echo sa_e($pg['tenant'] ? $pg['tenant'] : '—'); ?></td>
                        <td><?php echo sa_e($pg['referencia']); ?></td>
                        <td><?php echo sa_moeda($pg['valor']); ?></td>
                        <td><?php echo $pg['status'] == 'pago' ? '<span class="badge ok">Pago</span>' : '<span class="badge warn">Pendente</span>'; ?></td>
                        <td class="agora"><?php echo $pg['pago_em'] ? sa_data($pg['pago_em']) : '—'; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
<?php
sa_rodape();
