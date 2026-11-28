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
// Assinatura da primeira linha de cada arquivo gerado. Tambem e o criterio
// de limpeza: so removemos arquivos com esta marca (ver o loop final),
// porque /etc/caddy/sites tambem guarda blocos de outros sistemas.
$assinatura = '# gerado por /usr/local/sbin/beauty-sync-tenants.php - nao editar';
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

// Hosts que ja tem bloco proprio no Caddyfile: a instalacao principal e a
// raiz/www do dominio base. Eles nunca podem virar bloco de tenant, senao o
// Caddy recusa dois blocos para o mesmo endereco.
$hosts_principais = array($host_principal, 'www.' . $host_principal);
$base_env = getenv('SA_DOMINIO_BASE');
if ($base_env === false || $base_env === '') {
    $base_env = getenv('BB_DOMINIO_BASE');
}
$base_env = strtolower(trim((string) $base_env));
if ($base_env !== '') {
    $hosts_principais[] = $base_env;
    $hosts_principais[] = 'www.' . $base_env;
}

try {
    $pdo = new PDO(
        'mysql:dbname=' . getenv('DB_NAME') . ';host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';charset=utf8',
        getenv('DB_USER'),
        getenv('DB_PASSWORD')
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $linhas = $pdo->query("SELECT d.hostname, d.tipo, d.verificado_em, t.status FROM tenant_dominios d
        INNER JOIN tenants t ON t.id = d.tenant_id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    fwrite(STDERR, 'banco indisponivel: ' . $e->getMessage() . "\n");
    exit(1);
}

$bloqueados = array('suspenso', 'cancelado', 'cancelada', 'inativo');
$desejados = array();

foreach ($linhas as $linha) {
    $host = strtolower(trim((string) $linha['hostname']));
    if ($host === '' || in_array($host, $hosts_principais, true)) {
        continue;
    }
    if (!preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', $host) || strpos($host, '.') === false) {
        continue;
    }
    if (in_array(strtolower(trim((string) $linha['status'])), $bloqueados, true)) {
        continue;
    }

    // Dominio proprio so entra no Caddy depois de verificado. Antes disso o
    // host nao resolve tenant no PHP e nao deve receber certificado.
    $tipo = strtolower(trim((string) $linha['tipo']));
    if ($tipo === 'proprio' && empty($linha['verificado_em'])) {
        continue;
    }

    // O host pode chegar como www.: o resolvedor normaliza e o bloco
    // atende as duas formas.
    $enderecos = 'https://' . $host;
    if ($tipo === 'proprio' && strpos($host, 'www.') !== 0) {
        $enderecos .= ', https://www.' . $host;
    }

    $arquivo = $dir_destino . '/' . $prefixo . $host . '.caddy';
    $desejados[$arquivo] = $assinatura . "\n"
        . $enderecos . " {\n"
        . "\troot * /var/www/beauty\n"
        . "\tencode gzip\n"
        . "\tphp_fastcgi unix//run/php/php8.4-fpm.sock {\n"
        . "\t\ttry_files {path} {path}.php {path}/index.php index.php\n"
        . "\t}\n"
        // Mesma protecao do bloco principal no Caddyfile: sem ela o docroot
        // inteiro fica baixavel no subdominio do cliente (README.md,
        // docker-compose.yml, dumps .sql, scripts do deploy).
        . "\t@sensivel path_regexp (?i)(^/[.][^/]+|^/(caddy|docker)file$|[.](md|ya?ml|sql|gz|zip|log|sh|bat|ini|env|json|lock|bak|old|orig|swp)$)\n"
        . "\trespond @sensivel 404\n"
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
    if (isset($desejados[$antigo])) {
        continue;
    }
    // Apaga apenas o que ESTE gerador escreveu. O diretorio e compartilhado
    // com outros sistemas (o agreste.shop gera os blocos dele la), e um sync
    // da barbearia nao pode derrubar um site que nao e seu.
    $primeira = @file_get_contents($antigo, false, null, 0, strlen($assinatura));
    if ($primeira !== $assinatura) {
        continue;
    }
    @unlink($antigo);
    $mudou = true;
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
