#!/usr/bin/php
<?php
// ============================================================
// SINCRONIZA OS SUBDOMINIOS DOS CLIENTES COM O CADDY
// ------------------------------------------------------------
// Le tenant_dominios no banco de controle e mantem um bloco de site
// em /etc/caddy/sites/ para cada estabelecimento ativo. Com o DNS
// curinga *.iluminatto.online apontando para esta VPS, o subdominio
// de um cliente novo passa a responder (e a receber certificado)
// sozinho, no maximo um minuto depois do cadastro.
//
// Roda como root pelo systemd timer beauty-tenants.timer.
// ============================================================

$dir_destino = '/etc/caddy/sites';
$prefixo = 'tenant-';
$host_principal = 'beauty.iluminatto.online'; // bloco proprio no Caddyfile

if (!is_dir($dir_destino) && !@mkdir($dir_destino, 0755, true)) {
    fwrite(STDERR, "nao foi possivel criar $dir_destino\n");
    exit(1);
}

// Credenciais vem do pool do php-fpm (mesmas da aplicacao)
$pool = @file_get_contents('/etc/php/8.4/fpm/pool.d/www.conf');
if ($pool === false) {
    fwrite(STDERR, "nao foi possivel ler o pool do php-fpm\n");
    exit(1);
}
if (preg_match_all('/^env\[([A-Z_]+)\]\s*=\s*"?([^"\r\n]*)"?/m', $pool, $achados, PREG_SET_ORDER)) {
    foreach ($achados as $par) {
        putenv($par[1] . '=' . $par[2]);
    }
}

try {
    $pdo = new PDO(
        'mysql:dbname=' . getenv('DB_NAME') . ';host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8',
        getenv('DB_USER'),
        getenv('DB_PASSWORD')
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $linhas = $pdo->query("SELECT d.hostname, t.status FROM tenant_dominios d
        INNER JOIN tenants t ON t.id = d.tenant_id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    fwrite(STDERR, 'banco indisponivel: ' . $e->getMessage() . "\n");
    exit(1);
}

$bloqueados = array('suspenso', 'cancelado', 'cancelada', 'inativo');
$desejados = array();

foreach ($linhas as $linha) {
    $host = strtolower(trim((string) $linha['hostname']));
    if ($host === '' || $host === $host_principal) {
        continue;
    }
    if (!preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', $host) || strpos($host, '.') === false) {
        continue;
    }
    if (in_array(strtolower(trim((string) $linha['status'])), $bloqueados, true)) {
        continue;
    }

    $arquivo = $dir_destino . '/' . $prefixo . $host . '.caddy';
    $desejados[$arquivo] = "# gerado por /usr/local/sbin/beauty-sync-tenants.php - nao editar\n"
        . "https://" . $host . " {\n"
        . "\troot * /var/www/beauty\n"
        . "\tencode gzip\n"
        . "\tphp_fastcgi unix//run/php/php8.4-fpm.sock {\n"
        . "\t\ttry_files {path} {path}.php {path}/index.php index.php\n"
        . "\t}\n"
        . "\tfile_server\n"
        . "}\n";
}

$mudou = false;

foreach ($desejados as $arquivo => $conteudo) {
    if (!is_file($arquivo) || file_get_contents($arquivo) !== $conteudo) {
        file_put_contents($arquivo, $conteudo);
        $mudou = true;
    }
}

foreach (glob($dir_destino . '/' . $prefixo . '*.caddy') as $antigo) {
    if (!isset($desejados[$antigo])) {
        @unlink($antigo);
        $mudou = true;
    }
}

if (!$mudou) {
    echo "sem alteracoes\n";
    exit(0);
}

$saida = array();
$codigo = 0;
exec('caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile 2>&1', $saida, $codigo);
echo $codigo === 0 ? "caddy recarregado\n" : "falha ao recarregar o caddy\n" . implode("\n", $saida) . "\n";
exit($codigo === 0 ? 0 : 1);
