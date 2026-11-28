<?php
// ============================================================
// INDICE DE E-MAILS DE LOGIN DOS ESTABELECIMENTOS
// ------------------------------------------------------------
// O dominio principal (beautypro.site) enxerga apenas o banco de controle.
// Para levar quem digita um e-mail de administrador/funcionario ao login do
// estabelecimento certo, os logins de cada cliente ficam indexados em
// tenant_emails. O indice se mantem sozinho (provisionamento, painel e
// revalidacao diaria no login), mas este script recalcula tudo de uma vez.
//
// Na VPS, como root:
//   php scripts/tenant-emails-sync.php
//
// Em outra maquina, com as variaveis do banco:
//   DB_USER=... DB_PASSWORD=... DB_NAME=barbearia php scripts/tenant-emails-sync.php
//
// Codigo de saida 1 quando algum estabelecimento falha.
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    echo 'Nao encontrado.';
    exit;
}

require_once(__DIR__ . '/../sistema/tenant.php');

function tes_carregar_env($arquivo)
{
    if ($arquivo === '' || !is_readable($arquivo)) {
        return false;
    }
    $linhas = @file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$linhas) {
        return false;
    }
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#') {
            continue;
        }
        $pos = strpos($linha, '=');
        if ($pos === false) {
            continue;
        }
        $chave = trim(substr($linha, 0, $pos));
        $valor = trim(trim(substr($linha, $pos + 1)), "\"'");
        if ($chave !== '' && getenv($chave) === false) {
            putenv($chave . '=' . $valor);
        }
    }
    return true;
}

if (getenv('DB_PASSWORD') === false && getenv('MYSQL_URL') === false && getenv('DATABASE_URL') === false) {
    $env_file = (string) (getenv('DB_ENV_FILE') ?: dirname(__DIR__) . '/.secrets/db.env');
    tes_carregar_env($env_file);
}

$cred = bb_credenciais_banco();
try {
    $pdo_controle = new PDO(
        'mysql:dbname=' . $cred['banco'] . ';host=' . $cred['host'] . ';port=' . $cred['porta'] . ';charset=utf8',
        $cred['usuario'],
        $cred['senha']
    );
    $pdo_controle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    fwrite(STDERR, 'Nao conectou no banco de controle: ' . $e->getMessage() . "\n");
    exit(1);
}

if (!bb_tenant_emails_tabela($pdo_controle)) {
    fwrite(STDERR, "Nao foi possivel criar a tabela tenant_emails.\n");
    exit(1);
}

$tenants = $pdo_controle->query('SELECT id, slug, email FROM tenants ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
$falhas = 0;

foreach ($tenants as $tenant) {
    $ok = bb_tenant_emails_sincronizar($pdo_controle, (int) $tenant['id']);
    $total = 0;
    if ($ok) {
        $q = $pdo_controle->prepare('SELECT COUNT(*) FROM tenant_emails WHERE tenant_id = :id');
        $q->bindValue(':id', (int) $tenant['id'], PDO::PARAM_INT);
        $q->execute();
        $total = (int) $q->fetchColumn();
    } else {
        $falhas++;
    }
    printf("%-32s id=%d logins=%d %s\n", (string) $tenant['slug'], (int) $tenant['id'], $total, $ok ? 'ok' : 'FALHOU');
}

exit($falhas > 0 ? 1 : 0);
