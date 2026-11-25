<?php
// Gera um link de redefinicao de senha para o administrador do estabelecimento.
// Serve como recuperacao assistida: o suporte copia o link e envia ao cliente
// por WhatsApp, sem depender de e-mail configurado.
require_once(__DIR__ . '/_auth.php');
require_once(__DIR__ . '/../sistema/recuperacao.php');
sa_exigir_login();
sa_garantir_tabelas($pdo);
bb_tenant_garantir_colunas($pdo);

$metodo = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
if ($metodo !== 'POST') {
    header('Location: tenants.php');
    exit;
}

$csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
if (empty($_SESSION['sa_csrf']) || !hash_equals((string) $_SESSION['sa_csrf'], $csrf)) {
    header('Location: tenants.php?erro=' . urlencode('sessão expirada, tente de novo'));
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    header('Location: tenants.php');
    exit;
}

$q = $pdo->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
$q->bindValue(':id', $id, PDO::PARAM_INT);
$q->execute();
$tenant = $q->fetch(PDO::FETCH_ASSOC);
if (!$tenant) {
    header('Location: tenants.php?erro=' . urlencode('estabelecimento não encontrado'));
    exit;
}

$erro = '';
$link = '';
$admin = false;

try {
    $pdo_tenant = bb_pdo_banco(bb_tenant_banco($tenant));
    bb_rec_garantir_tabela($pdo_tenant);
    $admin = $pdo_tenant->query("SELECT id, nome, email FROM usuarios
        WHERE nivel = 'Administrador' ORDER BY (ativo = 'Sim') DESC, id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $erro = 'não foi possível abrir o banco do estabelecimento';
}

if ($erro === '' && !$admin) {
    $erro = 'este estabelecimento ainda não tem usuário administrador';
}

if ($erro === '') {
    $token = bb_rec_criar($pdo_tenant, (int) $admin['id'], 24);
    if (!$token) {
        $erro = 'não foi possível gerar o link';
    } else {
        $base = defined('SA_DOMINIO_BASE') ? SA_DOMINIO_BASE : (defined('BB_DOMINIO_BASE') ? BB_DOMINIO_BASE : 'iluminatto.online');
        $link = 'https://' . $tenant['slug'] . '.' . $base . '/sistema/recuperar-senha.php?token=' . $token['token'];
    }
}

sa_topo('Link de redefinição');
?>
    <h1>Link de redefinição de senha</h1>
    <p class="sub">
        Link de uso único para o administrador do estabelecimento criar uma nova senha.
        Copie e envie ao responsável (WhatsApp, e-mail etc.).
    </p>

    <div class="painel">
        <p style="margin-top:0">
            <strong>Estabelecimento:</strong> <?php echo sa_e($tenant['nome']); ?> (<?php echo sa_e($tenant['slug']); ?>)<br>
            <?php if ($admin) { ?>
                <strong>Usuário:</strong> <?php echo sa_e($admin['nome']); ?> · <?php echo sa_e($admin['email']); ?>
            <?php } ?>
        </p>

        <?php if ($erro !== '') { ?>
            <div class="aviso erro">Não foi possível gerar o link: <?php echo sa_e($erro); ?>.</div>
        <?php } else { ?>
            <div class="aviso ok">Link válido por 24 horas. Depois de usado, deixa de funcionar.</div>
            <p style="word-break:break-all;font-size:14px">
                <a href="<?php echo sa_e($link); ?>" target="_blank" rel="noopener"><?php echo sa_e($link); ?></a>
            </p>
            <p style="font-size:13px;color:#9aa4b2">
                Ao abrir o link, o cliente define a nova senha e volta para a tela de login.
            </p>
        <?php } ?>

        <p style="margin-bottom:0">
            <a class="btn" href="tenants.php">Voltar para estabelecimentos</a>
        </p>
    </div>
<?php
sa_rodape();
