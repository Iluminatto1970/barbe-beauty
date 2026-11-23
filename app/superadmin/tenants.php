<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
bb_tenant_garantir_colunas($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tenant = array('id' => '', 'nome' => '', 'slug' => '', 'email' => '', 'telefone' => '', 'plano_id' => '', 'status' => 'trial', 'vencimento' => '');

if ($id > 0) {
    $q = $pdo->prepare("SELECT * FROM tenants WHERE id = :id");
    $q->bindValue(':id', $id);
    $q->execute();
    $registro = $q->fetch(PDO::FETCH_ASSOC);
    if ($registro) {
        $tenant = $registro;
    }
}

$planos = $pdo->query("SELECT * FROM saas_planos WHERE ativo = 'Sim' ORDER BY valor ASC")->fetchAll(PDO::FETCH_ASSOC);

if (empty($_SESSION['sa_csrf'])) {
    $_SESSION['sa_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['sa_csrf'];

$lista = $pdo->query("SELECT t.*, p.nome AS plano, p.valor,
        (SELECT d.hostname FROM tenant_dominios d WHERE d.tenant_id = t.id ORDER BY d.tipo = 'subdominio' DESC LIMIT 1) AS dominio
    FROM tenants t LEFT JOIN saas_planos p ON p.id = t.plano_id
    ORDER BY t.id DESC")->fetchAll(PDO::FETCH_ASSOC);

sa_topo('Estabelecimentos');
?>
    <h1>Estabelecimentos</h1>
    <p class="sub">Cada estabelecimento tem um subdomínio e uma assinatura vinculada a um plano.</p>

    <?php if (isset($_GET['ok'])) { ?>
        <div class="aviso ok">Estabelecimento salvo com sucesso.</div>
    <?php } ?>
    <?php if (isset($_GET['erro'])) { ?>
        <div class="aviso erro">Não foi possível salvar: <?php echo sa_e($_GET['erro']); ?></div>
    <?php } ?>

    <div class="painel">
        <h2 style="margin-top:0"><?php echo $id > 0 ? 'Editar estabelecimento' : 'Novo estabelecimento'; ?></h2>
        <form method="post" action="tenant-salvar.php" class="grid">
            <input type="hidden" name="id" value="<?php echo sa_e($tenant['id']); ?>">

            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required maxlength="100" value="<?php echo sa_e($tenant['nome']); ?>">
            </div>
            <div class="campo">
                <label for="slug">Subdomínio (slug)</label>
                <input type="text" id="slug" name="slug" required maxlength="40" placeholder="beauty" value="<?php echo sa_e($tenant['slug']); ?>">
            </div>
            <div class="campo">
                <label for="email">E-mail do responsável</label>
                <input type="email" id="email" name="email" maxlength="120" value="<?php echo sa_e($tenant['email']); ?>">
            </div>
            <div class="campo">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" maxlength="20" value="<?php echo sa_e($tenant['telefone']); ?>">
            </div>
            <div class="campo">
                <label for="plano_id">Plano</label>
                <select id="plano_id" name="plano_id">
                    <option value="">Sem plano</option>
                    <?php foreach ($planos as $pl) { ?>
                        <option value="<?php echo (int) $pl['id']; ?>" <?php echo ((string) $tenant['plano_id'] === (string) $pl['id']) ? 'selected' : ''; ?>>
                            <?php echo sa_e($pl['nome']); ?> · <?php echo sa_moeda($pl['valor']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="campo">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (array('pendente' => 'Aguardando pagamento', 'trial' => 'Trial', 'ativo' => 'Ativo', 'suspenso' => 'Suspenso', 'cancelado' => 'Cancelado') as $valor => $rotulo) { ?>
                        <option value="<?php echo $valor; ?>" <?php echo $tenant['status'] == $valor ? 'selected' : ''; ?>><?php echo $rotulo; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="campo">
                <label for="vencimento">Vencimento</label>
                <input type="date" id="vencimento" name="vencimento" value="<?php echo sa_e(substr((string) $tenant['vencimento'], 0, 10)); ?>">
            </div>
            <div class="campo">
                <label for="senha">Senha do administrador</label>
                <input type="password" id="senha" name="senha" minlength="6" autocomplete="new-password" <?php echo $id > 0 ? '' : 'required'; ?> placeholder="<?php echo $id > 0 ? 'Deixe em branco para manter' : 'Mínimo 6 caracteres'; ?>">
                <small class="muted">Usada para entrar no painel do estabelecimento.</small>
            </div>

            <div class="acoes-form" style="grid-column: 1 / -1;">
                <button type="submit" class="btn primario">Salvar</button>
                <?php if ($id > 0) { ?>
                    <a class="btn" href="tenants.php">Cancelar</a>
                <?php } ?>
            </div>
        </form>
    </div>

    <h2>Lista</h2>
    <div class="painel">
        <?php if (count($lista) == 0) { ?>
            <p class="muted">Nenhum estabelecimento cadastrado. Crie o primeiro acima.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>#</th><th>Estabelecimento</th><th>Domínio</th><th>Plano</th><th>Status</th><th>Vencimento</th><th class="dir">Ações</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lista as $t) { ?>
                    <tr>
                        <td><?php echo (int) $t['id']; ?></td>
                        <td>
                            <strong><?php echo sa_e($t['nome']); ?></strong><br>
                            <small class="muted"><?php echo sa_e($t['slug']); ?> · <?php echo sa_e($t['email']); ?></small>
                        </td>
                        <td class="agora">
                            <?php echo $t['dominio'] ? '<a href="https://' . sa_e($t['dominio']) . '" target="_blank" rel="noopener">' . sa_e($t['dominio']) . '</a>' : '—'; ?>
                        </td>
                        <td>
                            <?php echo sa_e($t['plano'] ? $t['plano'] : '—'); ?>
                            <?php if ($t['valor'] !== null) { ?><br><small class="muted"><?php echo sa_moeda($t['valor']); ?>/mês</small><?php } ?>
                        </td>
                        <td><?php echo sa_status_badge($t['status']); ?></td>
                        <td class="agora"><?php echo sa_data($t['vencimento']); ?></td>
                        <td class="dir agora">
                            <a class="btn pequeno" href="tenants.php?id=<?php echo (int) $t['id']; ?>">Editar</a>
                            <a class="btn pequeno" href="tenant-status.php?id=<?php echo (int) $t['id']; ?>"><?php echo $t['status'] == 'ativo' ? 'Suspender' : 'Ativar'; ?></a>
                            <?php if (empty($t['db_name'])) { ?>
                                <a class="btn pequeno" href="tenant-provisionar.php?id=<?php echo (int) $t['id']; ?>">Preparar banco</a>
                            <?php } ?>
                            <?php if (!empty($t['email'])) { ?>
                                <a class="btn pequeno" href="tenant-boas-vindas.php?id=<?php echo (int) $t['id']; ?>"
                                   onclick="return confirm('Enviar o e-mail de boas-vindas com as instruções de acesso?');">Enviar e-mail</a>
                            <?php } ?>
                            <form method="post" action="tenant-excluir.php" style="display:inline"
                                  onsubmit="return confirm('Excluir este estabelecimento, o banco de dados e os registros de assinatura?');">
                                <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                                <input type="hidden" name="csrf" value="<?php echo sa_e($csrf); ?>">
                                <button type="submit" class="btn pequeno perigo" style="border:0;cursor:pointer">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>
<?php
sa_rodape();
