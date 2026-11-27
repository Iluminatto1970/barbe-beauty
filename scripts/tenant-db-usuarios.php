<?php
// ============================================================
// USUARIO MYSQL EXCLUSIVO POR ESTABELECIMENTO
// ------------------------------------------------------------
// Cria (ou corrige) o usuario MySQL de cada tenant com GRANT restrito
// ao schema dele e marca tenants.db_user. Sem isso, um tenant criado
// antes deste recurso continua entrando com a credencial compartilhada.
//
// Precisa de uma credencial MySQL com privilegio de CREATE USER/GRANT
// (root, normalmente). Em producao, rode na VPS como root:
//
//   php scripts/tenant-db-usuarios.php --aplicar \
//        --fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf
//
// Ou, de qualquer maquina, com as variaveis de ambiente do banco:
//
//   DB_USER=root DB_PASSWORD=... php scripts/tenant-db-usuarios.php --aplicar
//
// Sem --aplicar o script so mostra a situacao. --verificar conecta com a
// credencial de cada tenant e prova o isolamento (nao enxerga e nao le
// o banco de outro cliente). Codigo de saida 1 quando algo falha.
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    echo 'Nao encontrado.';
    exit;
}

require_once(__DIR__ . '/../sistema/tenant.php');

function tdu_erro($mensagem)
{
    fwrite(STDERR, $mensagem . "\n");
    exit(1);
}

function tdu_carregar_env($arquivo)
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

function tdu_carregar_fpm($arquivo)
{
    if ($arquivo === '' || !is_readable($arquivo)) {
        tdu_erro('Arquivo do PHP-FPM ilegivel: ' . $arquivo . ' (rode como root)');
    }
    $cfg = parse_ini_file($arquivo, false, INI_SCANNER_RAW);
    if (!is_array($cfg) || empty($cfg['env']) || !is_array($cfg['env'])) {
        tdu_erro('Nao foi possivel ler env[] do PHP-FPM: ' . $arquivo);
    }
    foreach ($cfg['env'] as $chave => $valor) {
        if (getenv($chave) === false) {
            putenv($chave . '=' . $valor);
        }
    }
}

$opcoes = array(
    'aplicar'       => false,
    'verificar'     => false,
    'imprimir_sql'  => false,
    'fpm_conf'      => '',
    'env_file'      => '',
);

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--aplicar') {
        $opcoes['aplicar'] = true;
    } elseif ($arg === '--verificar') {
        $opcoes['verificar'] = true;
    } elseif ($arg === '--imprimir-sql') {
        $opcoes['imprimir_sql'] = true;
    } elseif (strpos($arg, '--fpm-conf=') === 0) {
        $opcoes['fpm_conf'] = trim(substr($arg, 11));
    } elseif (strpos($arg, '--env-file=') === 0) {
        $opcoes['env_file'] = trim(substr($arg, 11));
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Uso: php scripts/tenant-db-usuarios.php [--aplicar] [--verificar] [--imprimir-sql]"
            . " [--fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf] [--env-file=/caminho/db.env]\n";
        exit(0);
    } else {
        tdu_erro('Opcao desconhecida: ' . $arg);
    }
}

if ($opcoes['fpm_conf'] !== '') {
    tdu_carregar_fpm($opcoes['fpm_conf']);
} else {
    $env_file = $opcoes['env_file'];
    if ($env_file === '') {
        $env_file = (string) (getenv('DB_ENV_FILE') ?: dirname(__DIR__) . '/.secrets/db.env');
    }
    tdu_carregar_env($env_file);
}

$controle = bb_banco_controle();

try {
    $pdo = bb_pdo_banco($controle);
} catch (Exception $e) {
    tdu_erro('Falha ao conectar no banco de controle: ' . $e->getMessage());
}

// Os GRANTs que faltam na credencial de controle, prontos para o mysql.
// Sai antes de qualquer escrita: `--imprimir-sql | mysql` como root.
if ($opcoes['imprimir_sql']) {
    $conta = $pdo->query('SELECT CURRENT_USER()')->fetchColumn();
    $partes = explode('@', (string) $conta, 2);
    $usuario_controle = $partes[0] !== '' ? $partes[0] : bb_credenciais_banco()['usuario'];
    $host_controle = isset($partes[1]) && $partes[1] !== '' ? $partes[1] : 'localhost';

    // SEM escapar o `_` do prefixo: o MariaDB casa GRANT OPTION contra o
    // padrao literal em mysql.db, e `barbearia\_t%` nao casa com
    // `barbearia_t10` (1044). Com `barbearia_t%` o `_` e coringa de um
    // caractere e o padrao alcanca os tenants - e continua nao alcancando
    // `barbearia` nem `barbearia_demo` (conferido na VPS).
    $padrao = str_replace(array('`', '\\'), '', bb_tenant_prefixo()) . '%';
    $alvo = "'" . $usuario_controle . "'@'" . $host_controle . "'";

    echo "-- Libera o provisionamento do usuario MySQL exclusivo de cada tenant\n";
    echo "-- (CREATE USER global + GRANT OPTION no schema do tenant).\n";
    echo "-- Aplicar: php scripts/tenant-db-usuarios.php --imprimir-sql | mysql\n";
    echo 'GRANT CREATE USER ON *.* TO ' . $alvo . ";\n";
    echo 'GRANT ALL PRIVILEGES ON `' . $padrao . '`.* TO ' . $alvo . " WITH GRANT OPTION;\n";
    echo "FLUSH PRIVILEGES;\n";
    exit(0);
}

// Garante tenants.db_user (tambem entra pela migracao 008)
bb_tenant_garantir_colunas($pdo);

$tenants = $pdo->query('SELECT id, slug, nome, db_name, db_user FROM tenants ORDER BY id')
    ->fetchAll(PDO::FETCH_ASSOC);

if (count($tenants) === 0) {
    echo "nenhum estabelecimento cadastrado\n";
    exit(0);
}

$falhas = 0;
$pendentes = 0;
$dados = array();

echo "banco de controle: {$controle}\n";
echo ($opcoes['aplicar'] ? "modo: aplicar\n" : "modo: simulacao (use --aplicar)\n");
echo "\n";

foreach ($tenants as $tenant) {
    $tenant_id = (int) $tenant['id'];
    $slug = (string) $tenant['slug'];
    $banco = trim((string) $tenant['db_name']);

    if ($banco === '') {
        $banco = bb_tenant_nome_banco($tenant_id);
    }
    if ($banco === $controle) {
        echo "  - {$slug} (#{$tenant_id}): instalacao principal, sem usuario dedicado\n";
        $tenant['db_name'] = $banco;
        $dados[] = $tenant;
        continue;
    }

    $usuario = bb_tenant_db_usuario($tenant_id);

    if (!$opcoes['aplicar']) {
        $estado = (!empty($tenant['db_user']) && $tenant['db_user'] === $usuario)
            ? 'provisionado'
            : 'PENDENTE (ainda usa a credencial compartilhada)';
        if ($estado !== 'provisionado') {
            $pendentes++;
        }
        echo "  - {$slug} (#{$tenant_id}): banco={$banco} usuario={$usuario} -> {$estado}\n";
        $tenant['db_name'] = $banco;
        $dados[] = $tenant;
        continue;
    }

    $criacao = bb_tenant_db_criar_usuario($pdo, $banco, $tenant_id);
    if (!$criacao['ok']) {
        echo "  FALHA {$slug} (#{$tenant_id}): {$criacao['erro']}\n";
        $falhas++;
        continue;
    }

    $pdo->prepare('UPDATE tenants SET db_user = :usuario WHERE id = :id')
        ->execute(array(':usuario' => $usuario, ':id' => $tenant_id));

    $tenant['db_user'] = $usuario;
    $tenant['db_name'] = $banco;
    $dados[] = $tenant;
    echo "  ok {$slug} (#{$tenant_id}): usuario={$usuario} GRANT em `{$banco}`.*\n";
}

if ($opcoes['verificar']) {
    echo "\nverificando isolamento\n";

    $outros = array();
    foreach ($dados as $tenant) {
        $banco = trim((string) $tenant['db_name']);
        if ($banco !== '' && $banco !== $controle) {
            $outros[] = $banco;
        }
    }

    foreach ($dados as $tenant) {
        $tenant_id = (int) $tenant['id'];
        $slug = (string) $tenant['slug'];
        $banco = trim((string) $tenant['db_name']);

        if ($banco === '' || $banco === $controle) {
            continue;
        }

        $credenciais = bb_tenant_db_credenciais($tenant);
        if (!$credenciais) {
            echo "  PENDENTE {$slug}: sem credencial dedicada\n";
            $falhas++;
            continue;
        }

        try {
            $pdo_tenant = bb_pdo_tenant($credenciais);
        } catch (Exception $e) {
            echo "  FALHA {$slug}: {$e->getMessage()}\n";
            $falhas++;
            continue;
        }

        $visiveis = $pdo_tenant->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        $fora = array_values(array_diff($visiveis, array($banco, 'information_schema')));
        if (count($fora) > 0) {
            echo "  VAZAMENTO {$slug}: enxerga " . implode(', ', $fora) . "\n";
            $falhas++;
            continue;
        }

        // Prova ativa: ler o banco de outro cliente tem de ser negado.
        $alheio = '';
        foreach ($outros as $candidato) {
            if ($candidato !== $banco) {
                $alheio = $candidato;
                break;
            }
        }
        if ($alheio !== '') {
            try {
                $pdo_tenant->query('SELECT 1 FROM `' . $alheio . '`.`config` LIMIT 1');
                echo "  VAZAMENTO {$slug}: conseguiu ler `{$alheio}`\n";
                $falhas++;
                continue;
            } catch (Exception $e) {
                // esperado: sem GRANT em outro schema
            }
        }

        echo "  ok {$slug}: so ve `{$banco}`" . ($alheio !== '' ? " e nao le `{$alheio}`" : '') . "\n";
    }
}

echo "\n";
if ($falhas > 0) {
    echo "{$falhas} problema(s) encontrado(s).\n";
    exit(1);
}
if (!$opcoes['aplicar'] && $pendentes > 0) {
    // Nao dizer "nada pendente" junto: sao casos que dependem de --aplicar.
    echo "{$pendentes} estabelecimento(s) ainda na credencial compartilhada;"
        . " rode com --aplicar (ver deploy/tenant-db.md).\n";
    exit(0);
}
echo "nada pendente.\n";
exit(0);
