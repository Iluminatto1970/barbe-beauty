<?php
// ============================================================
// MIGRACOES DE SCHEMA - Barbearia PRO
// ------------------------------------------------------------
// Cada estabelecimento tem o seu proprio banco. Toda mudanca de
// estrutura precisa chegar a TODOS os bancos, inclusive os que ja
// existem. Para isso:
//
//   sql/tenant_schema.sql     estrutura canonica (novo cliente)
//   sql/migracoes/NNN_*.sql   mudancas incrementais (bancos atuais)
//
// Regras das migracoes:
//   - todo comando DEVE ser idempotente (IF NOT EXISTS, INSERT ...
//     WHERE NOT EXISTS, checagem em information_schema). Assim ela
//     pode rodar de novo depois de um provisionamento, sem erro;
//   - uma migracao por assunto, numerada em sequencia (001, 002...);
//   - sem DELIMITER/procedures: os comandos sao separados por ";".
//
// O historico fica na tabela schema_versoes de cada banco.
// Execucao: php app/sistema/migrar.php
// ============================================================

if (!defined('BB_MIGRACOES_TABELA')) {
    define('BB_MIGRACOES_TABELA', 'schema_versoes');
}

if (!function_exists('bb_migracoes_raiz')) {
    // Raiz do projeto em qualquer layout:
    //   repositorio: app/sistema/... + sql/...      (dois niveis acima)
    //   deploy web:  sistema/... + sql/...          (um nivel acima)
    function bb_migracoes_raiz()
    {
        static $raiz = null;
        if ($raiz !== null) {
            return $raiz;
        }
        $candidatos = array(dirname(__DIR__, 2), dirname(__DIR__, 1));
        foreach ($candidatos as $candidato) {
            if (is_dir($candidato . '/sql/migracoes')) {
                $raiz = $candidato;
                return $raiz;
            }
        }
        $raiz = dirname(__DIR__, 2);
        return $raiz;
    }
}

if (!function_exists('bb_migracoes_diretorio')) {
    function bb_migracoes_diretorio()
    {
        $env = getenv('BB_MIGRACOES_DIR');
        if ($env !== false && $env !== '') {
            return rtrim($env, '/\\');
        }
        return bb_migracoes_raiz() . '/sql/migracoes';
    }
}

if (!function_exists('bb_migracoes_listar')) {
    // Lista as migracoes do projeto, ordenadas por versao.
    // Retorna array de array('versao' => int, 'arquivo' => nome, 'caminho' => absoluto)
    function bb_migracoes_listar()
    {
        static $lista = null;
        if ($lista !== null) {
            return $lista;
        }

        $lista = array();
        $diretorio = bb_migracoes_diretorio();
        if (!is_dir($diretorio)) {
            return $lista;
        }

        $arquivos = glob($diretorio . '/*.sql');
        if (!$arquivos) {
            return $lista;
        }

        foreach ($arquivos as $caminho) {
            $nome = basename($caminho);
            if (!preg_match('/^(\d{3,})_([a-zA-Z0-9_-]+)\.sql$/', $nome, $m)) {
                continue;
            }
            $versao = (int) $m[1];
            $lista[$versao] = array(
                'versao'  => $versao,
                'arquivo' => $nome,
                'caminho' => $caminho,
            );
        }

        ksort($lista);
        $lista = array_values($lista);
        return $lista;
    }
}

if (!function_exists('bb_migracoes_versao_final')) {
    // Ultima versao existente no projeto (0 = nenhuma migracao)
    function bb_migracoes_versao_final()
    {
        $lista = bb_migracoes_listar();
        if (count($lista) === 0) {
            return 0;
        }
        $ultima = end($lista);
        return (int) $ultima['versao'];
    }
}

if (!function_exists('bb_migracoes_dividir')) {
    // Separa um arquivo .sql em comandos (ignora linhas de comentario)
    function bb_migracoes_dividir($conteudo)
    {
        $conteudo = (string) $conteudo;
        if (trim($conteudo) === '') {
            return array();
        }

        $linhas = preg_split('/\R/', $conteudo);
        $uteis = array();
        foreach ($linhas as $linha) {
            $inicio = ltrim($linha);
            if ($inicio === '' || strpos($inicio, '--') === 0 || strpos($inicio, '#') === 0) {
                continue;
            }
            $uteis[] = $linha;
        }

        $sql = trim(implode("\n", $uteis));
        if ($sql === '') {
            return array();
        }

        $comandos = array();
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $parte) {
            $parte = trim($parte);
            if ($parte !== '') {
                $comandos[] = $parte;
            }
        }
        return $comandos;
    }
}

if (!function_exists('bb_migracoes_garantir_tabela')) {
    // Cria o historico de versoes no banco informado
    function bb_migracoes_garantir_tabela($pdo)
    {
        $tabela = BB_MIGRACOES_TABELA;
        $pdo->exec("CREATE TABLE IF NOT EXISTS `$tabela` (
            versao INT UNSIGNED NOT NULL,
            arquivo VARCHAR(120) NOT NULL,
            aplicada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (versao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

if (!function_exists('bb_migracoes_versao')) {
    // Versao aplicada no banco (0 quando ainda nao ha historico)
    function bb_migracoes_versao($pdo)
    {
        try {
            bb_migracoes_garantir_tabela($pdo);
            $versao = $pdo->query('SELECT COALESCE(MAX(versao), 0) FROM `' . BB_MIGRACOES_TABELA . '`')->fetchColumn();
            return (int) $versao;
        } catch (Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('bb_migracoes_aplicar')) {
    // Aplica as migracoes pendentes no banco informado.
    // Opcoes: 'simular' (nao executa), 'forcar' (reexecuta tudo),
    //         'ate' (para numa versao especifica)
    function bb_migracoes_aplicar($pdo, $opcoes = array())
    {
        $resultado = array(
            'ok'        => false,
            'aplicadas' => array(),
            'versao'    => 0,
            'erro'      => '',
            'simulado'  => !empty($opcoes['simular']),
        );

        if (!($pdo instanceof PDO)) {
            $resultado['erro'] = 'conexao invalida';
            return $resultado;
        }

        $simular = !empty($opcoes['simular']);
        $forcar  = !empty($opcoes['forcar']);
        $ate     = isset($opcoes['ate']) ? (int) $opcoes['ate'] : 0;

        $lista = bb_migracoes_listar();

        try {
            bb_migracoes_garantir_tabela($pdo);
            $versao = bb_migracoes_versao($pdo);
        } catch (Exception $e) {
            $resultado['erro'] = 'nao foi possivel ler o historico: ' . $e->getMessage();
            return $resultado;
        }

        foreach ($lista as $migracao) {
            if ($ate > 0 && $migracao['versao'] > $ate) {
                break;
            }
            if (!$forcar && $migracao['versao'] <= $versao) {
                continue;
            }

            $conteudo = @file_get_contents($migracao['caminho']);
            if ($conteudo === false) {
                $resultado['erro'] = 'nao foi possivel ler ' . $migracao['arquivo'];
                $resultado['versao'] = $versao;
                return $resultado;
            }

            if ($simular) {
                $resultado['aplicadas'][] = $migracao['arquivo'];
                $versao = $migracao['versao'];
                continue;
            }

            $comandos = bb_migracoes_dividir($conteudo);
            $indice = 0;
            try {
                foreach ($comandos as $comando) {
                    $indice++;
                    $pdo->exec($comando);
                }
                $registrar = $pdo->prepare('INSERT IGNORE INTO `' . BB_MIGRACOES_TABELA . '` (versao, arquivo) VALUES (?, ?)');
                $registrar->execute(array($migracao['versao'], $migracao['arquivo']));
            } catch (Exception $e) {
                $resultado['erro'] = 'migracao ' . $migracao['arquivo'] . ' falhou no comando ' . $indice . ': ' . $e->getMessage();
                $resultado['versao'] = $versao;
                return $resultado;
            }

            $versao = $migracao['versao'];
            $resultado['aplicadas'][] = $migracao['arquivo'];
        }

        $resultado['versao'] = $simular ? $versao : bb_migracoes_versao($pdo);
        $resultado['ok'] = true;
        return $resultado;
    }
}

if (!function_exists('bb_migracoes_sincronizar')) {
    // Garante o historico e aplica o que falta (usado no provisionamento
    // e no botao do super admin). Idempotente.
    function bb_migracoes_sincronizar($pdo, $simular = false)
    {
        return bb_migracoes_aplicar($pdo, array('simular' => (bool) $simular));
    }
}

if (!function_exists('bb_tenant_schema_sql')) {
    // Estrutura canonica do estabelecimento (sql/tenant_schema.sql)
    function bb_tenant_schema_sql()
    {
        static $sql = null;
        if ($sql !== null) {
            return $sql;
        }

        $caminho = getenv('BB_TENANT_SCHEMA');
        if ($caminho === false || $caminho === '') {
            $caminho = bb_migracoes_raiz() . '/sql/tenant_schema.sql';
        }
        $conteudo = @file_get_contents($caminho);
        $sql = ($conteudo === false) ? '' : $conteudo;
        return $sql;
    }
}

if (!function_exists('bb_tenant_criar_schema')) {
    // Cria as tabelas do sistema no banco do tenant a partir do
    // schema canonico. Nao depende do banco de controle.
    function bb_tenant_criar_schema($pdo, $sql = null)
    {
        $resultado = array('ok' => false, 'erro' => '', 'comandos' => 0);

        if ($sql === null) {
            $sql = bb_tenant_schema_sql();
        }
        if (trim((string) $sql) === '') {
            $resultado['erro'] = 'schema canonico vazio';
            return $resultado;
        }

        $comandos = bb_migracoes_dividir($sql);
        if (count($comandos) === 0) {
            $resultado['erro'] = 'nenhum comando no schema canonico';
            return $resultado;
        }

        foreach ($comandos as $indice => $comando) {
            try {
                $pdo->exec($comando);
            } catch (Exception $e) {
                $resultado['erro'] = 'comando ' . ($indice + 1) . ': ' . $e->getMessage();
                return $resultado;
            }
        }

        $resultado['comandos'] = count($comandos);
        $resultado['ok'] = true;
        return $resultado;
    }
}
