<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);

$filtro = isset($_GET['status']) ? (string) $_GET['status'] : '';
$sql = "SELECT t.*, p.nome AS plano, p.valor,
        (SELECT d.hostname FROM tenant_dominios d WHERE d.tenant_id = t.id ORDER BY d.tipo = 'subdominio' DESC LIMIT 1) AS dominio
    FROM tenants t LEFT JOIN saas_planos p ON p.id = t.plano_id";
$parametros = array();

if (in_array($filtro, array('ativo', 'trial', 'suspenso', 'cancelado'))) {
    $sql .= " WHERE t.status = :status";
    $parametros[':status'] = $filtro;
}
$sql .= " ORDER BY t.vencimento ASC, t.nome ASC";

$q = $pdo->prepare($sql);
$q->execute($parametros);
$lista = $q->fetchAll(PDO::FETCH_ASSOC);

$historico = $pdo->query("SELECT pg.*, t.nome AS tenant
    FROM saas_pagamentos pg LEFT JOIN tenants t ON t.id = pg.tenant_id
    ORDER BY pg.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

$total_ativos = 0;
foreach ($lista as $linha) {
    if ($linha['status'] == 'ativo' and $linha['valor'] !== null) {
        $total_ativos += (float) $linha['valor'];
    }
}

sa_topo('Assinaturas');
?>
    <h1>Assinaturas</h1>
    <p class="sub">Cobrança mensal por estabelecimento. Registrar pagamento ativa o cliente e prorroga o vencimento em 30 dias.</p>

    <?php if (isset($_GET['ok'])) { ?>
        <div class="aviso ok">Pagamento registrado e vencimento atualizado.</div>
    <?php } ?>

    <div class="cards">
        <div class="card">
            <div class="rotulo">Assinaturas listadas</div>
            <div class="valor"><?php echo count($lista); ?></div>
        </div>
        <div class="card">
            <div class="rotulo">MRR (ativos)</div>
            <div class="valor pequeno"><?php echo sa_moeda($total_ativos); ?></div>
        </div>
    </div>

    <h2>Filtro</h2>
    <div class="painel" style="padding:12px 18px;">
        <a class="btn pequeno <?php echo $filtro === '' ? 'primario' : ''; ?>" href="assinaturas.php">Todos</a>
        <a class="btn pequeno <?php echo $filtro === 'ativo' ? 'primario' : ''; ?>" href="assinaturas.php?status=ativo">Ativos</a>
        <a class="btn pequeno <?php echo $filtro === 'trial' ? 'primario' : ''; ?>" href="assinaturas.php?status=trial">Trial</a>
        <a class="btn pequeno <?php echo $filtro === 'suspenso' ? 'primario' : ''; ?>" href="assinaturas.php?status=suspenso">Suspensos</a>
        <a class="btn pequeno <?php echo $filtro === 'cancelado' ? 'primario' : ''; ?>" href="assinaturas.php?status=cancelado">Cancelados</a>
    </div>

    <h2>Cobranças</h2>
    <div class="painel">
        <?php if (count($lista) == 0) { ?>
            <p class="muted">Nenhuma assinatura encontrada.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>Estabelecimento</th><th>Plano</th><th>Mensalidade</th><th>Vencimento</th><th>Status</th><th class="dir">Ação</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $t) { ?>
                    <tr>
                        <td>
                            <strong><?php echo sa_e($t['nome']); ?></strong><br>
                            <small class="muted"><?php echo sa_e($t['dominio'] ? $t['dominio'] : $t['slug']); ?></small>
                        </td>
                        <td><?php echo sa_e($t['plano'] ? $t['plano'] : '—'); ?></td>
                        <td><?php echo sa_moeda($t['valor']); ?></td>
                        <td class="agora"><?php echo sa_data($t['vencimento']); ?></td>
                        <td><?php echo sa_status_badge($t['status']); ?></td>
                        <td class="dir agora">
                            <form method="post" action="assinatura-pagar.php" style="display:flex; gap:8px; justify-content:flex-end; align-items:center;">
                                <input type="hidden" name="tenant_id" value="<?php echo (int) $t['id']; ?>">
                                <input type="hidden" name="valor" value="<?php echo sa_e($t['valor'] !== null ? $t['valor'] : '0'); ?>">
                                <input type="hidden" name="referencia" value="<?php echo date('Y-m'); ?>">
                                <button type="submit" class="btn pequeno primario">Registrar pagamento</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <h2>Últimos pagamentos</h2>
    <div class="painel">
        <?php if (count($historico) == 0) { ?>
            <p class="muted">Nenhum pagamento registrado.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>Estabelecimento</th><th>Referência</th><th>Valor</th><th>Status</th><th>Pago em</th></tr>
                </thead>
                <tbody>
                <?php foreach ($historico as $pg) { ?>
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
