<?php
// ============================================================
// CREDENCIAL MYSQL POR ESTABELECIMENTO
// ------------------------------------------------------------
// Cada tenant tem o seu proprio banco. Ate aqui todas as conexoes
// usavam a mesma credencial do banco de controle (DB_USER/DB_PASSWORD),
// que enxerga todos os schemas. Quem conseguisse rodar SQL pelo site
// de um cliente (injection, arquivo inclusado, painel comprometido)
// lia os bancos de todos os outros.
//
// Agora cada estabelecimento tem um usuario MySQL proprio:
//
//   usuario  bb_t<id>            (ex.: bb_t3)
//   senha    HMAC-SHA256(chave, "barbearia-pro/tenant-db/v1/<id>")
//   GRANT    <privilegios do painel> ON `<db do tenant>`.*  (nada mais)
//
// A senha NAO fica gravada no banco de controle: e derivada da chave
// (TENANT_DB_KEY, arquivo .secrets ou env do PHP-FPM+DB_PASSWORD), que
// nao esta ao alcance de uma consulta SQL. Assim uma leitura da tabela
// `tenants` nao entrega a credencial de nenhum cliente.
//
// Quem provisiona: bb_tenant_db_criar_usuario (precisa de CREATE USER).
// O painel chama no provisionamento e, se necessario, no primeiro
// acesso de um tenant que ainda nao tem usuario (bb_tenant_db_preparar).
// Para conferir tudo de uma vez: php scripts/tenant-db-usuarios.php
// ============================================================

if (!function_exists('bb_tenant_db_chave')) {
    // Segredo usado para derivar as senhas. Pode vir de:
    //   1. TENANT_DB_KEY          (variavel de ambiente)
    //   2. TENANT_DB_KEY_FILE     (padrao: .secrets/tenant-db.key)
    //   3. DB_PASSWORD + DB_NAME  (ultimo recurso: a credencial de controle)
    function bb_tenant_db_chave()
    {
        static $chave = null;
        if ($chave !== null) {
            return $chave;
        }

        $env = getenv('TENANT_DB_KEY');
        if ($env !== false && trim((string) $env) !== '') {
            $chave = trim((string) $env);
            return $chave;
        }

        $arquivos = array();
        $arquivo_env = getenv('TENANT_DB_KEY_FILE');
        if ($arquivo_env !== false && trim((string) $arquivo_env) !== '') {
            $arquivos[] = trim((string) $arquivo_env);
        }
        $arquivos[] = dirname(__DIR__) . '/.secrets/tenant-db.key';
        $arquivos[] = '/var/www/beauty-secrets/tenant-db.key';

        foreach ($arquivos as $arquivo) {
            if (is_readable($arquivo)) {
                $conteudo = trim((string) @file_get_contents($arquivo));
                if ($conteudo !== '') {
                    $chave = $conteudo;
                    return $chave;
                }
            }
        }

        $cred = bb_credenciais_banco();
        $chave = hash_hmac(
            'sha256',
            'barbearia-pro/tenant-db/chave|' . $cred['banco'],
            (string) $cred['senha']
        );
        return $chave;
    }
}

if (!function_exists('bb_tenant_db_usuario')) {
    // Nome do usuario MySQL do estabelecimento (limite do MySQL: 32 chars)
    function bb_tenant_db_usuario($tenant_id)
    {
        return 'bb_t' . (int) $tenant_id;
    }
}

if (!function_exists('bb_tenant_db_senha')) {
    // Senha derivada: nao esta gravada em lugar nenhum, so e recalculada.
    function bb_tenant_db_senha($tenant_id)
    {
        return substr(hash_hmac(
            'sha256',
            'barbearia-pro/tenant-db/v1/' . (int) $tenant_id,
            bb_tenant_db_chave()
        ), 0, 40);
    }
}

if (!function_exists('bb_tenant_db_hosts')) {
    // O PHP conecta por socket (localhost) ou por TCP (127.0.0.1),
    // dependendo de DB_HOST. Criar os dois evita erro de acesso.
    function bb_tenant_db_hosts()
    {
        return array('localhost', '127.0.0.1');
    }
}

if (!function_exists('bb_tenant_db_privilegios')) {
    // O que o painel faz no banco do proprio tenant: CRUD, migrations
    // (ALTER/CREATE/INDEX), relatorios (VIEW) e arquivos temporarios.
    // Sem GRANT OPTION, sem CREATE USER, sem acesso a outro schema.
    function bb_tenant_db_privilegios()
    {
        return 'SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, '
            . 'REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES, '
            . 'CREATE VIEW, SHOW VIEW, TRIGGER';
    }
}

if (!function_exists('bb_tenant_db_criar_usuario')) {
    // Cria (ou corrige) o usuario MySQL do estabelecimento com GRANT
    // restrito ao schema dele. Idempotente: pode rodar a cada deploy.
    // Retorna array('ok' => bool, 'usuario' => string, 'erro' => string).
    function bb_tenant_db_criar_usuario($pdo_controle, $banco, $tenant_id)
    {
        $tenant_id = (int) $tenant_id;
        $resultado = array(
            'ok'      => false,
            'usuario' => bb_tenant_db_usuario($tenant_id),
            'erro'    => '',
        );

        if ($tenant_id <= 0) {
            $resultado['erro'] = 'estabelecimento invalido';
            return $resultado;
        }
        if (!($pdo_controle instanceof PDO)) {
            $resultado['erro'] = 'conexao de controle indisponivel';
            return $resultado;
        }

        $banco = trim((string) $banco);
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $banco)) {
            $resultado['erro'] = 'nome de banco invalido';
            return $resultado;
        }

        $usuario = $resultado['usuario'];
        $senha = bb_tenant_db_senha($tenant_id);

        try {
            foreach (bb_tenant_db_hosts() as $host) {
                // CREATE USER sem privilegios; ALTER garante a senha derivada
                // (rotacao automatica se a chave mudar).
                $pdo_controle->exec("CREATE USER IF NOT EXISTS '$usuario'@'$host' IDENTIFIED BY '$senha'");
                $pdo_controle->exec("ALTER USER '$usuario'@'$host' IDENTIFIED BY '$senha'");

                // Zera qualquer privilegio antigo (inclusive de um banco que
                // o tenant usava antes) e concede SO o schema atual.
                $pdo_controle->exec("REVOKE ALL PRIVILEGES, GRANT OPTION FROM '$usuario'@'$host'");
                $pdo_controle->exec(
                    'GRANT ' . bb_tenant_db_privilegios() . " ON `$banco`.* TO '$usuario'@'$host'"
                );
            }
        } catch (Exception $e) {
            $resultado['erro'] = $e->getMessage();
            // 1227 = falta CREATE USER (global); 1044 = falta GRANT OPTION
            // no schema do tenant. O comando que libera esta em
            // deploy/tenant-db-privilegios.sql.
            if (strpos($e->getMessage(), ' 1227 ') !== false
                || strpos($e->getMessage(), ' 1044 ') !== false) {
                $resultado['erro'] .= ' (falta privilegio no usuario de controle:'
                    . ' aplique deploy/tenant-db-privilegios.sql como root do MySQL)';
            }
            return $resultado;
        }

        $resultado['ok'] = true;
        return $resultado;
    }
}

if (!function_exists('bb_tenant_db_credenciais')) {
    // Credencial dedicada do tenant, ou null quando ele ainda nao tem
    // usuario MySQL (nesse caso quem chama decide o fallback).
    function bb_tenant_db_credenciais($tenant)
    {
        if (!is_array($tenant)) {
            return null;
        }

        $tenant_id = isset($tenant['id']) ? (int) $tenant['id'] : 0;
        if ($tenant_id <= 0) {
            return null;
        }
        if (empty($tenant['db_user'])) {
            return null;
        }

        $banco = isset($tenant['db_name']) ? trim((string) $tenant['db_name']) : '';
        if ($banco === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $banco)) {
            return null;
        }
        if ($banco === bb_banco_controle()) {
            return null; // instalacao principal usa a credencial de controle
        }

        $cred = bb_credenciais_banco();
        return array(
            'host'    => $cred['host'],
            'porta'   => $cred['porta'],
            'banco'   => $banco,
            'usuario' => bb_tenant_db_usuario($tenant_id),
            'senha'   => bb_tenant_db_senha($tenant_id),
        );
    }
}

if (!function_exists('bb_pdo_tenant')) {
    // Conexao com o banco do estabelecimento usando a credencial dele.
    function bb_pdo_tenant($credenciais)
    {
        $pdo = new PDO(
            'mysql:dbname=' . $credenciais['banco'] . ';host=' . $credenciais['host']
                . ';port=' . $credenciais['porta'] . ';charset=utf8',
            $credenciais['usuario'],
            $credenciais['senha']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
}

if (!function_exists('bb_tenant_db_preparar')) {
    // Provisiona a credencial no primeiro acesso de um tenant antigo.
    // Uma tentativa por requisicao: falha de privilegio nao entra em loop.
    function bb_tenant_db_preparar($pdo_controle, $tenant)
    {
        static $tentados = array();

        if (!is_array($tenant) || !($pdo_controle instanceof PDO)) {
            return null;
        }
        $tenant_id = isset($tenant['id']) ? (int) $tenant['id'] : 0;
        if ($tenant_id <= 0 || isset($tentados[$tenant_id])) {
            return null;
        }
        $tentados[$tenant_id] = true;

        $banco = isset($tenant['db_name']) ? trim((string) $tenant['db_name']) : '';
        if ($banco === '') {
            $banco = bb_tenant_nome_banco($tenant_id);
        }
        if ($banco === bb_banco_controle()) {
            return null;
        }

        $criacao = bb_tenant_db_criar_usuario($pdo_controle, $banco, $tenant_id);
        if (!$criacao['ok']) {
            // Sem privilegio de CREATE USER o painel continua com a
            // credencial compartilhada; o operador roda o script com uma
            // credencial privilegiada e o proximo acesso ja usa a dedicada.
            error_log('tenant-db: usuario ' . $criacao['usuario'] . ' nao provisionado: ' . $criacao['erro']);
            return null;
        }

        try {
            $pdo_controle->prepare('UPDATE tenants SET db_user = :usuario WHERE id = :id')
                ->execute(array(':usuario' => $criacao['usuario'], ':id' => $tenant_id));
        } catch (Exception $e) {
            // O usuario existe e a senha e derivada: o painel usa mesmo sem
            // a marca no banco de controle.
            error_log('tenant-db: nao foi possivel marcar tenants.db_user: ' . $e->getMessage());
        }

        $tenant['db_user'] = $criacao['usuario'];
        $tenant['db_name'] = $banco;
        return bb_tenant_db_credenciais($tenant);
    }
}
