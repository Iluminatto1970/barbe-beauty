<?php
// ============================================================
// MIGRAR BANCOS - Barbearia PRO (linha de comando)
// ------------------------------------------------------------
// Aplica as migracoes pendentes (sql/migracoes/*.sql) em todos os
// bancos: o banco de controle (instalacao principal) e o banco de
// cada estabelecimento.
//
// Uso:
//   php app/sistema/migrar.php                 (todos os bancos)
//   php app/sistema/migrar.php --simular       (so lista o que falta)
//   php app/sistema/migrar.php --tenant=beauty (um estabelecimento)
//   php app/sistema/migrar.php --forcar        (reexecuta tudo)
//
// Variaveis de ambiente: DB_HOST, DB_PORT, DB_NAME, DB_USER,
// DB_PASSWORD (ou MYSQL_URL/DATABASE_URL), TENANT_DB_PREFIX.
// Aceita tambem o arquivo .secrets/db.env (DB_ENV_FILE para outro).
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    echo 'Nao encontrado.';
    exit;
}

require_once(__DIR__ . '/tenant.php');
require_once(__DIR__ . '/migracoes.php');

if (!function_exists('bb_cli_carregar_env')) {
    // Carrega KEY=VALOR de um arquivo, sem sobrescrever o ambiente
    function bb_cli_carregar_env($caminho)
    {
        if ($caminho === '' || !is_readable($caminho)) {
            return false;
        }
        $linhas = @file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
}

$arquivo_env = getenv('DB_ENV_FILE');
bb_cli_carregar_env(($arquivo_env !== false && $arquivo_env !== '') ? $arquivo_env : dirname(__DIR__, 2) . '/.secrets/db.env');

$opcoes = array('todos' => true, 'tenant' => '', 'simular' => false, 'forcar' => false);
foreach (array_slice($argv, 1) as $argumento) {
    if ($argumento === '--simular' || $argumento === '--dry-run') {
        $opcoes['simular'] = true;
    } elseif ($argumento === '--forcar') {
        $opcoes['forcar'] = true;
    } elseif (strpos($argumento, '--tenant=') === 0) {
        $opcoes['tenant'] = trim(substr($argumento, 9));
    } elseif ($argumento === '--ajuda' || $argumento === '--help' || $argumento === '-h') {
        echo "Uso: php app/sistema/migrar.php [--tenant=slug|id] [--simular] [--forcar]\n";
        exit(0);
    } else {
        fwrite(STDERR, 'Opcao desconhecida: ' . $argumento . "\n");
        exit(2);
    }
}

$banco_controle = bb_banco_controle();
$versao_codigo = bb_migracoes_versao_final();

echo "Migracoes do projeto: v" . $versao_codigo . " (" . count(bb_migracoes_listar()) . " arquivo(s))\n";
echo "Banco de controle: " . $banco_controle . "\n";

try {
    $pdo_controle = bb_pdo_banco($banco_controle);
} catch (Exception $e) {
    fwrite(STDERR, "Falha ao conectar no banco de controle: " . $e->getMessage() . "\n");
    exit(1);
}

bb_tenant_garantir_colunas($pdo_controle);

$consulta = "SELECT id, nome, slug, db_name FROM tenants";
$parametros = array();
if ($opcoes['tenant'] !== '') {
    if (ctype_digit($opcoes['tenant'])) {
        $consulta .= " WHERE id = :valor";
    } else {
        $consulta .= " WHERE slug = :valor";
    }
    $parametros[':valor'] = $opcoes['tenant'];
}
$consulta .= " ORDER BY id";

$q = $pdo_controle->prepare($consulta);
$q->execute($parametros);
$tenants = $q->fetchAll(PDO::FETCH_ASSOC);

if (count($tenants) === 0) {
    fwrite(STDERR, "Nenhum estabelecimento encontrado.\n");
    exit(1);
}

$processados = array();
$falhas = 0;
$total_aplicadas = 0;

foreach ($tenants as $tenant) {
    $db = bb_tenant_banco($tenant);
    $rotulo = $tenant['nome'] . ' (' . $db . ')';

    if (isset($processados[$db])) {
        echo "  = " . $rotulo . ": mesmo banco de " . $processados[$db] . ", ignorado\n";
        continue;
    }
    $processados[$db] = $tenant['nome'];

    try {
        $pdo_db = ($db === $banco_controle) ? $pdo_controle : bb_pdo_banco($db);
        $resultado = bb_migracoes_aplicar($pdo_db, array(
            'simular' => $opcoes['simular'],
            'forcar'  => $opcoes['forcar'],
        ));
    } catch (Exception $e) {
        fwrite(STDERR, "  x " . $rotulo . ": " . $e->getMessage() . "\n");
        $falhas++;
        continue;
    }

    if (!$resultado['ok']) {
        fwrite(STDERR, "  x " . $rotulo . ": " . $resultado['erro'] . "\n");
        $falhas++;
        continue;
    }

    $aplicadas = count($resultado['aplicadas']);
    $total_aplicadas += $aplicadas;

    if (!$opcoes['simular']) {
        try {
            $pdo_controle->prepare("UPDATE tenants SET schema_version = :versao WHERE id = :id")
                ->execute(array(':versao' => (int) $resultado['versao'], ':id' => (int) $tenant['id']));
        } catch (Exception $e) {
            fwrite(STDERR, "  ! " . $rotulo . ": migrado, mas nao registrou a versao: " . $e->getMessage() . "\n");
            $falhas++;
            continue;
        }
    }

    echo "  " . ($aplicadas > 0 ? "+" : "=") . " " . $rotulo . ": v" . (int) $resultado['versao']
        . ($aplicadas > 0 ? " (" . $aplicadas . " aplicada(s))" : " (em dia)")
        . ($opcoes['simular'] && $aplicadas > 0 ? " [simulado]" : "")
        . "\n";
}

echo "\n" . ($falhas === 0 ? "OK" : "FALHAS: " . $falhas) . " · bancos: " . count($processados)
    . " · migracoes aplicadas: " . $total_aplicadas . "\n";

exit($falhas === 0 ? 0 : 1);
