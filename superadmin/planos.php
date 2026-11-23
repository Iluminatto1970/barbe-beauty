<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$plano = array('id' => '', 'nome' => '', 'valor' => '0.00', 'limite_profissionais' => '0', 'limite_agendamentos' => '0', 'ativo' => 'Sim');

if ($id > 0) {
    $q = $pdo->prepare("SELECT * FROM saas_planos WHERE id = :id");
    $q->bindValue(':id', $id);
    $q->execute();
    $registro = $q->fetch(PDO::FETCH_ASSOC);
    if ($registro) {
        $plano = $registro;
    }
}

$lista = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM tenants t WHERE t.plano_id = p.id) AS assinantes
    FROM saas_planos p ORDER BY p.valor ASC")->fetchAll(PDO::FETCH_ASSOC);

sa_topo('Planos');
?>
    <h1>Planos</h1>
    <p class="sub">Defina preço mensal e limites de uso de cada plano.</p>

    <div class="painel">
        <h2 style="margin-top:0"><?php echo $id > 0 ? 'Editar plano' : 'Novo plano'; ?></h2>
        <form method="post" action="plano-salvar.php" class="grid">
            <input type="hidden" name="id" value="<?php echo sa_e($plano['id']); ?>">

            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required maxlength="60" value="<?php echo sa_e($plano['nome']); ?>">
            </div>
            <div class="campo">
                <label for="valor">Valor mensal (R$)</label>
                <input type="number" step="0.01" min="0" id="valor" name="valor" required value="<?php echo sa_e($plano['valor']); ?>">
            </div>
            <div class="campo">
                <label for="limite_profissionais">Limite de profissionais (0 = ilimitado)</label>
                <input type="number" min="0" id="limite_profissionais" name="limite_profissionais" value="<?php echo (int) $plano['limite_profissionais']; ?>">
            </div>
            <div class="campo">
                <label for="limite_agendamentos">Limite de agendamentos/mês (0 = ilimitado)</label>
                <input type="number" min="0" id="limite_agendamentos" name="limite_agendamentos" value="<?php echo (int) $plano['limite_agendamentos']; ?>">
            </div>
            <div class="campo">
                <label for="ativo">Ativo</label>
                <select id="ativo" name="ativo">
                    <option value="Sim" <?php echo $plano['ativo'] == 'Sim' ? 'selected' : ''; ?>>Sim</option>
                    <option value="Não" <?php echo $plano['ativo'] == 'Não' ? 'selected' : ''; ?>>Não</option>
                </select>
            </div>

            <div class="acoes-form" style="grid-column: 1 / -1;">
                <button type="submit" class="btn primario">Salvar</button>
                <?php if ($id > 0) { ?><a class="btn" href="planos.php">Cancelar</a><?php } ?>
            </div>
        </form>
    </div>

    <h2>Planos cadastrados</h2>
    <div class="painel">
        <?php if (count($lista) == 0) { ?>
            <p class="muted">Nenhum plano cadastrado.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>#</th><th>Plano</th><th>Mensalidade</th><th>Profissionais</th><th>Agendamentos/mês</th><th>Assinantes</th><th>Status</th><th class="dir">Ações</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $p) { ?>
                    <tr>
                        <td><?php echo (int) $p['id']; ?></td>
                        <td><strong><?php echo sa_e($p['nome']); ?></strong></td>
                        <td><?php echo sa_moeda($p['valor']); ?></td>
                        <td><?php echo (int) $p['limite_profissionais'] > 0 ? (int) $p['limite_profissionais'] : 'Ilimitado'; ?></td>
                        <td><?php echo (int) $p['limite_agendamentos'] > 0 ? (int) $p['limite_agendamentos'] : 'Ilimitado'; ?></td>
                        <td><?php echo (int) $p['assinantes']; ?></td>
                        <td><?php echo $p['ativo'] == 'Sim' ? '<span class="badge ok">Ativo</span>' : '<span class="badge">Inativo</span>'; ?></td>
                        <td class="dir agora">
                            <a class="btn pequeno" href="planos.php?id=<?php echo (int) $p['id']; ?>">Editar</a>
                            <a class="btn pequeno perigo" href="plano-excluir.php?id=<?php echo (int) $p['id']; ?>" onclick="return confirm('Excluir este plano?');">Excluir</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
<?php
sa_rodape();
