<?php
require_once(__DIR__ . '/_auth.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
bb_tenant_garantir_colunas($pdo);
$versao_codigo = bb_migracoes_versao_final();

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
        <div class="aviso ok"><?php echo ($_GET['ok'] === '1' || $_GET['ok'] === '') ? 'Estabelecimento salvo com sucesso.' : sa_e($_GET['ok']); ?></div>
    <?php } ?>
    <?php if (isset($_GET['erro'])) { ?>
        <div class="aviso erro">Não foi possível salvar: <?php echo sa_e($_GET['erro']); ?></div>
    <?php } ?>
    <?php if (isset($_GET['migracoes'])) { ?>
        <div class="aviso ok">Bancos sincronizados: <?php echo (int) $_GET['migracoes']; ?> migração(ões) aplicada(s) em <?php echo (int) @$_GET['bancos']; ?> banco(s).</div>
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

    <?php
    if ($id > 0) {
        bb_dominio_garantir_colunas($pdo);
        $q = $pdo->prepare("SELECT * FROM tenant_dominios WHERE tenant_id = :id ORDER BY (tipo = 'subdominio') DESC, hostname ASC");
        $q->bindValue(':id', $id, PDO::PARAM_INT);
        $q->execute();
        $dominios = $q->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="painel">
        <h2 style="margin-top:0">Domínio próprio</h2>
        <p class="sub">
            O site do estabelecimento responde em qualquer domínio verificado. A verificação prova que o cliente
            controla o DNS: crie o registro TXT (ou o CNAME) indicado e clique em Verificar.
            Enquanto não estiver verificado, o domínio não serve o site nem recebe certificado.
        </p>

        <?php if (count($dominios) == 0) { ?>
            <p class="muted">Nenhum domínio cadastrado. O subdomínio é criado junto com o estabelecimento.</p>
        <?php } ?>

        <?php foreach ($dominios as $d) {
            $situacao = bb_dominio_situacao($d);
        ?>
            <div style="border:1px solid #2a2f3a;border-radius:12px;padding:14px 16px;margin:0 0 12px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div>
                        <strong><?php echo sa_e($d['hostname']); ?></strong>
                        <span class="badge <?php echo sa_e($situacao['classe']); ?>" style="margin-left:8px"><?php echo sa_e($situacao['rotulo']); ?></span>
                        <?php if (!empty($d['registrado_em'])) { ?>
                            <br><small class="muted">Registrado em <?php echo sa_e($d['registrado_em']); ?><?php echo !empty($d['verificado_em']) ? ' · verificado em ' . sa_e($d['verificado_em']) : ''; ?></small>
                        <?php } ?>
                    </div>
                    <?php if (!empty($d['tipo']) && $d['tipo'] === 'proprio') { ?>
                        <form method="post" action="tenant-dominio.php" style="margin:0"
                              onsubmit="return confirm('Remover este domínio próprio? O site deixa de responder neste endereço.');">
                            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                            <input type="hidden" name="host" value="<?php echo sa_e($d['hostname']); ?>">
                            <input type="hidden" name="acao" value="remover">
                            <input type="hidden" name="csrf" value="<?php echo sa_e($csrf); ?>">
                            <button type="submit" class="btn pequeno perigo" style="border:0;cursor:pointer">Remover</button>
                        </form>
                    <?php } ?>
                </div>

                <?php if (!$situacao['verificado']) {
                    $registros = bb_dominio_registros_esperados($d['hostname'], $d['token_verificacao'], $tenant);
                ?>
                    <p class="muted" style="margin:12px 0 6px;font-size:13px">Crie um destes registros no DNS do domínio:</p>
                    <table style="margin:0 0 10px">
                        <thead><tr><th>Tipo</th><th>Nome</th><th>Valor</th></tr></thead>
                        <tbody>
                            <?php foreach ($registros as $r) { ?>
                                <tr>
                                    <td><?php echo sa_e($r['tipo']); ?></td>
                                    <td class="agora"><code><?php echo sa_e($r['nome']); ?></code></td>
                                    <td class="agora"><code><?php echo sa_e($r['valor']); ?></code></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <p class="muted" style="margin:0 0 10px;font-size:12px">
                        O TXT é a prova de propriedade. O CNAME também precisa apontar para esta VPS para o site responder.
                    </p>
                    <form method="post" action="tenant-dominio.php" class="acoes-form" style="margin:0">
                        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                        <input type="hidden" name="host" value="<?php echo sa_e($d['hostname']); ?>">
                        <input type="hidden" name="acao" value="verificar">
                        <input type="hidden" name="csrf" value="<?php echo sa_e($csrf); ?>">
                        <button type="submit" class="btn pequeno primario">Verificar agora</button>
                    </form>
                <?php } ?>
            </div>
        <?php } ?>

        <form method="post" action="tenant-dominio.php" class="acoes-form" style="margin:0">
            <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
            <input type="hidden" name="acao" value="registrar">
            <input type="hidden" name="csrf" value="<?php echo sa_e($csrf); ?>">
            <div class="campo" style="flex:1;min-width:240px;margin:0">
                <label for="dominio">Adicionar domínio próprio</label>
                <input type="text" id="dominio" name="host" maxlength="253" placeholder="barbearia.com.br">
            </div>
            <button type="submit" class="btn primario">Registrar domínio</button>
        </form>
    </div>
    <?php } ?>

    <h2>Lista</h2>
    <form method="post" action="tenant-migrar.php" class="acoes-form" style="margin:0 0 16px">
        <input type="hidden" name="csrf" value="<?php echo sa_e($csrf); ?>">
        <button type="submit" class="btn">Atualizar estrutura dos bancos</button>
        <span class="muted" style="font-size:13px">
            Versão do código: v<?php echo (int) $versao_codigo; ?> · aplica as migrações pendentes em cada banco
        </span>
    </form>
    <div class="painel">
        <?php if (count($lista) == 0) { ?>
            <p class="muted">Nenhum estabelecimento cadastrado. Crie o primeiro acima.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr><th>#</th><th>Estabelecimento</th><th>Domínio</th><th>Banco</th><th>Plano</th><th>Status</th><th>Vencimento</th><th class="dir">Ações</th></tr>
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
                        <td class="agora">
                            <small class="muted"><?php echo sa_e(!empty($t['db_name']) ? $t['db_name'] : '—'); ?></small><br>
                            <small class="muted">schema v<?php echo (int) $t['schema_version']; ?> / v<?php echo (int) $versao_codigo; ?></small>
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
