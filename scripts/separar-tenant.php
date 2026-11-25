<?php
// ============================================================
// SEPARAR UM TENANT DO BANCO DE CONTROLE
// ------------------------------------------------------------
// Copia as tabelas de um estabelecimento do banco de controle para
// um banco proprio (ex.: barbearia_t1), confere contagem e hash de
// cada tabela e atualiza tenants.db_name. Com --drop-control, remove
// do controle as tabelas que sao do estabelecimento, deixando apenas
// tenants, tenant_dominios, saas_planos, saas_pagamentos e super_admins.
//
// Uso:
//   php scripts/separar-tenant.php --tenant=beauty --target=barbearia_t1 --apply --site-demo=1
//   php scripts/separar-tenant.php --tenant=beauty --target=barbearia_t1 --apply --drop-control
//
// Em producao, os dados de conexao podem vir do pool do PHP-FPM:
//   php scripts/separar-tenant.php --fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf ...
//
// Sem --apply o script so mostra o plano. O backup do banco de controle
// deve ser feito ANTES; este script nao substitui backup.
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    echo 'Nao encontrado.';
    exit;
}

require_once(__DIR__ . '/../sistema/tenant.php');
require_once(__DIR__ . '/../sistema/migracoes.php');

function st_erro($mensagem)
{
    fwrite(STDERR, $mensagem . "\n");
    exit(1);
}

function st_carregar_env($arquivo)
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
        $valor = trim(substr($linha, $pos + 1));
        $valor = trim($valor, "\"'");
        if ($chave !== '' && getenv($chave) === false) {
            putenv($chave . '=' . $valor);
        }
    }
    return true;
}

function st_carregar_fpm($arquivo)
{
    if ($arquivo === '' || !is_readable($arquivo)) {
        st_erro('Arquivo do PHP-FPM nao encontrado: ' . $arquivo);
    }
    $cfg = parse_ini_file($arquivo, false, INI_SCANNER_RAW);
    if (!is_array($cfg) || empty($cfg['env']) || !is_array($cfg['env'])) {
        st_erro('Nao foi possivel ler env[] do PHP-FPM: ' . $arquivo);
    }
    foreach ($cfg['env'] as $chave => $valor) {
        if (getenv($chave) === false) {
            putenv($chave . '=' . $valor);
        }
    }
}

function st_nome_ok($nome)
{
    return (bool) preg_match('/^[a-zA-Z0-9_]+$/', (string) $nome);
}

function st_hash_tabela($pdo, $banco, $tabela)
{
    $sql = 'SELECT * FROM `' . $banco . '`.`' . $tabela . '`';
    $linhas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $normalizadas = array();
    foreach ($linhas as $linha) {
        ksort($linha);
        $normalizadas[] = json_encode($linha, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    sort($normalizadas, SORT_STRING);
    return hash('sha256', implode("\n", $normalizadas));
}

function st_contagens($pdo, $banco, $tabelas)
{
    $resultado = array();
    foreach ($tabelas as $tabela) {
        $resultado[$tabela] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $banco . '`.`' . $tabela . '`')->fetchColumn();
    }
    return $resultado;
}

$opcoes = array(
    'tenant'       => '',
    'target'       => '',
    'site_demo'    => 0,
    'apply'        => false,
    'drop_control' => false,
    'fpm_conf'     => '',
);

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--apply') {
        $opcoes['apply'] = true;
    } elseif ($arg === '--drop-control') {
        $opcoes['drop_control'] = true;
    } elseif (strpos($arg, '--tenant=') === 0) {
        $opcoes['tenant'] = trim(substr($arg, 9));
    } elseif (strpos($arg, '--target=') === 0) {
        $opcoes['target'] = trim(substr($arg, 9));
    } elseif (strpos($arg, '--site-demo=') === 0) {
        $opcoes['site_demo'] = ((int) substr($arg, 12) === 1) ? 1 : 0;
    } elseif (strpos($arg, '--fpm-conf=') === 0) {
        $opcoes['fpm_conf'] = trim(substr($arg, 11));
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Uso: php scripts/separar-tenant.php --tenant=slug --target=banco [--apply] [--site-demo=1] [--drop-control] [--fpm-conf=/caminho/www.conf]\n";
        exit(0);
    } else {
        st_erro('Opcao desconhecida: ' . $arg);
    }
}

if ($opcoes['fpm_conf'] !== '') {
    st_carregar_fpm($opcoes['fpm_conf']);
} else {
    $env_file = getenv('DB_ENV_FILE');
    st_carregar_env(($env_file !== false && $env_file !== '') ? $env_file : dirname(__DIR__) . '/.secrets/db.env');
}

if ($opcoes['tenant'] === '') {
    st_erro('Informe --tenant=slug ou --tenant=id.');
}
if ($opcoes['target'] === '') {
    st_erro('Informe --target=banco.');
}

$controle = bb_banco_controle();
$target = $opcoes['target'];
if (!st_nome_ok($target)) {
    st_erro('Nome de banco invalido.');
}
if ($target === $controle) {
    st_erro('O banco de destino precisa ser diferente do banco de controle.');
}

try {
    $pdo = bb_pdo_banco($controle);
} catch (Exception $e) {
    st_erro('Falha ao conectar no banco de controle: ' . $e->getMessage());
}

// Coluna de controle que autoriza o site publico a continuar em demonstracao.
try {
    $tem_site_demo = (bool) $pdo->query("SHOW COLUMNS FROM tenants LIKE 'site_demo'")->fetch();
    if (!$tem_site_demo) {
        $pdo->exec("ALTER TABLE tenants ADD COLUMN site_demo TINYINT(1) NOT NULL DEFAULT 0");
        echo "coluna tenants.site_demo criada\n";
    }
} catch (Exception $e) {
    st_erro('Nao foi possivel garantir tenants.site_demo: ' . $e->getMessage());
}

$consulta = "SELECT * FROM tenants WHERE " . (ctype_digit($opcoes['tenant']) ? 'id' : 'slug') . " = :valor LIMIT 1";
$stmt = $pdo->prepare($consulta);
$stmt->execute(array(':valor' => $opcoes['tenant']));
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tenant) {
    st_erro('Estabelecimento nao encontrado: ' . $opcoes['tenant']);
}
$tenant_id = (int) $tenant['id'];

$tabelas_controle = array('tenants', 'tenant_dominios', 'saas_planos', 'saas_pagamentos', 'super_admins');
$todas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$tabelas_tenant = array_values(array_diff($todas, $tabelas_controle));
foreach ($tabelas_tenant as $tabela) {
    if (!st_nome_ok($tabela)) {
        st_erro('Nome de tabela inesperado: ' . $tabela);
    }
}

echo "controle={$controle} tenant={$tenant['slug']} id={$tenant_id} destino={$target}\n";
echo "tabelas de tenant=" . count($tabelas_tenant) . "\n";

if (!$opcoes['apply'] && !$opcoes['drop_control']) {
    echo "simulacao: nenhuma alteracao feita (use --apply)\n";
    exit(0);
}

if ($opcoes['apply']) {
    // Nunca copiar por cima de um banco que ja tem tabelas.
    $existentes = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($target))->fetchColumn();
    if ($existentes > 0) {
        st_erro('O banco de destino ja tem ' . $existentes . ' tabela(s); abortando para nao misturar dados.');
    }

    $criado = false;
    try {
        $pdo->exec('CREATE DATABASE `' . $target . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        $criado = true;
        $pdo_destino = bb_pdo_banco($target);

        // Estrutura copiada do banco atual do tenant (nao do schema canonico):
        // preserva qualquer coluna que ja exista em producao.
        foreach ($tabelas_tenant as $tabela) {
            $create = $pdo->query('SHOW CREATE TABLE `' . $tabela . '`')->fetch(PDO::FETCH_ASSOC);
            $ddl = isset($create['Create Table']) ? $create['Create Table'] : '';
            if ($ddl === '') {
                throw new Exception('SHOW CREATE TABLE vazio para ' . $tabela);
            }
            $ddl = preg_replace('/^CREATE TABLE `' . preg_quote($tabela, '/') . '`/', 'CREATE TABLE `' . $target . '`.`' . $tabela . '`', $ddl, 1);
            $pdo_destino->exec($ddl);
        }

        // Bloqueia escrita/leitura no controle durante a copia e a troca.
        $locks = array();
        foreach ($tabelas_tenant as $tabela) {
            $locks[] = '`' . $controle . '`.`' . $tabela . '` WRITE';
            $locks[] = '`' . $target . '`.`' . $tabela . '` WRITE';
        }
        $locks[] = '`' . $controle . '`.`tenants` WRITE';
        $pdo->exec('LOCK TABLES ' . implode(', ', $locks));

        try {
            foreach ($tabelas_tenant as $tabela) {
                $pdo->exec('INSERT INTO `' . $target . '`.`' . $tabela . '` SELECT * FROM `' . $controle . '`.`' . $tabela . '`');
            }

            $contagens_controle = st_contagens($pdo, $controle, $tabelas_tenant);
            $contagens_destino = st_contagens($pdo, $target, $tabelas_tenant);
            $divergencias = array();
            foreach ($tabelas_tenant as $tabela) {
                $hash_controle = st_hash_tabela($pdo, $controle, $tabela);
                $hash_destino = st_hash_tabela($pdo, $target, $tabela);
                if ($contagens_controle[$tabela] !== $contagens_destino[$tabela] || !hash_equals($hash_controle, $hash_destino)) {
                    $divergencias[] = $tabela . ' (' . $contagens_controle[$tabela] . ' -> ' . $contagens_destino[$tabela] . ')';
                }
            }
            if (count($divergencias) > 0) {
                throw new Exception('Divergencia na copia: ' . implode(', ', $divergencias));
            }

            $versao = (int) $pdo->query('SELECT COALESCE(MAX(versao), 0) FROM `' . $target . '`.`schema_versoes`')->fetchColumn();
            $up = $pdo->prepare('UPDATE tenants SET db_name = :db, site_demo = :demo, schema_version = :versao WHERE id = :id');
            $up->execute(array(':db' => $target, ':demo' => (int) $opcoes['site_demo'], ':versao' => $versao, ':id' => $tenant_id));
            echo "tenant atualizado para db_name={$target} site_demo=" . (int) $opcoes['site_demo'] . " schema_version={$versao}\n";
        } finally {
            $pdo->exec('UNLOCK TABLES');
        }

        echo "OK copia conferida: " . count($tabelas_tenant) . " tabela(s)\n";
        foreach ($tabelas_tenant as $tabela) {
            echo '  ' . $tabela . "\t" . $contagens_destino[$tabela] . "\n";
        }
    } catch (Exception $e) {
        if ($criado) {
            try {
                $pdo->exec('DROP DATABASE IF EXISTS `' . $target . '`');
            } catch (Exception $ex) {
                fwrite(STDERR, 'aviso: falha ao limpar banco parcial ' . $target . ': ' . $ex->getMessage() . "\n");
            }
        }
        st_erro('Falha na separacao: ' . $e->getMessage());
    }
}

if ($opcoes['drop_control']) {
    $stmt = $pdo->prepare('SELECT db_name, site_demo FROM tenants WHERE id = :id');
    $stmt->execute(array(':id' => $tenant_id));
    $atual = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$atual || $atual['db_name'] !== $target) {
        st_erro('O tenant ainda nao aponta para ' . $target . '; nao vou limpar o controle.');
    }

    $outros = (int) $pdo->query("SELECT COUNT(*) FROM tenants WHERE (db_name IS NULL OR db_name = '' OR db_name = " . $pdo->quote($controle) . ") AND id <> " . $tenant_id)->fetchColumn();
    if ($outros > 0) {
        st_erro('Existem outros tenants usando o banco de controle; limpeza abortada.');
    }

    // Confere tudo de novo antes de remover qualquer coisa do controle.
    // schema_versoes fica de fora: o banco novo pode ter migracoes a mais
    // (aplicadas depois da copia), e isso nao e perda de dado do tenant.
    foreach ($tabelas_tenant as $tabela) {
        if ($tabela === 'schema_versoes') {
            continue;
        }
        $a = (int) $pdo->query('SELECT COUNT(*) FROM `' . $controle . '`.`' . $tabela . '`')->fetchColumn();
        $b = (int) $pdo->query('SELECT COUNT(*) FROM `' . $target . '`.`' . $tabela . '`')->fetchColumn();
        if ($a !== $b || !hash_equals(st_hash_tabela($pdo, $controle, $tabela), st_hash_tabela($pdo, $target, $tabela))) {
            st_erro('Divergencia antes da limpeza em ' . $tabela . '; nada foi removido.');
        }
    }
    $versoes_controle = $pdo->query('SELECT versao FROM `' . $controle . '`.`schema_versoes`')->fetchAll(PDO::FETCH_COLUMN);
    $versoes_destino = $pdo->query('SELECT versao FROM `' . $target . '`.`schema_versoes`')->fetchAll(PDO::FETCH_COLUMN);
    if (count(array_diff($versoes_controle, $versoes_destino)) > 0) {
        st_erro('O banco novo nao tem todas as versoes de schema do controle; nada foi removido.');
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach ($tabelas_tenant as $tabela) {
            $pdo->exec('DROP TABLE `' . $controle . '`.`' . $tabela . '`');
            echo "removida do controle: {$tabela}\n";
        }
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
    echo "OK controle limpo; restaram: " . implode(', ', $tabelas_controle) . "\n";
}
