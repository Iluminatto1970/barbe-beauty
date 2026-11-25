<?php
// ============================================================
// MULTI-TENANT - Barbearia PRO
// ------------------------------------------------------------
// Cada estabelecimento (tenant) tem o seu proprio banco de
// dados. O banco de controle (DB_NAME) guarda apenas:
//   tenants, tenant_dominios, saas_planos, saas_pagamentos,
//   super_admins e a instalacao principal.
//
// A resolucao e feita pelo dominio da requisicao:
//   beauty.iluminatto.online  -> tenant_dominios -> banco do tenant
//   dominio principal         -> banco de controle
// ============================================================

if (!defined('BB_DOMINIO_BASE')) {
    $bb_dominio_env = getenv('SA_DOMINIO_BASE');
    define('BB_DOMINIO_BASE', ($bb_dominio_env !== false && $bb_dominio_env !== '') ? $bb_dominio_env : 'iluminatto.online');
}

// Estrutura canonica e migracoes versionadas por banco
require_once(__DIR__ . '/migracoes.php');

if (!function_exists('bb_normalizar_host')) {
    function bb_normalizar_host($host)
    {
        $host = strtolower(trim((string) $host));
        $host = preg_replace('/:\d+$/', '', $host);
        $host = rtrim($host, '.');
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        return $host;
    }
}

if (!function_exists('bb_host_atual')) {
    function bb_host_atual()
    {
        return bb_normalizar_host(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
    }
}

if (!function_exists('bb_banco_controle')) {
    function bb_banco_controle()
    {
        global $bb_banco_controle;
        if (!empty($bb_banco_controle)) {
            return $bb_banco_controle;
        }
        $banco = getenv('DB_NAME');
        return ($banco !== false && $banco !== '') ? $banco : 'barbearia';
    }
}

if (!function_exists('bb_tenant_prefixo')) {
    function bb_tenant_prefixo()
    {
        $prefixo = getenv('TENANT_DB_PREFIX');
        return ($prefixo !== false && $prefixo !== '') ? $prefixo : 'barbearia_t';
    }
}

if (!function_exists('bb_tenant_nome_banco')) {
    function bb_tenant_nome_banco($tenant_id)
    {
        return bb_tenant_prefixo() . (int) $tenant_id;
    }
}

if (!function_exists('bb_tenant_banco')) {
    // Banco que deve ser usado por um tenant (vazio = banco de controle)
    function bb_tenant_banco($tenant)
    {
        if (!$tenant) {
            return bb_banco_controle();
        }
        $db = isset($tenant['db_name']) ? trim((string) $tenant['db_name']) : '';
        if ($db === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
            return bb_banco_controle();
        }
        return $db;
    }
}

if (!function_exists('bb_banco_atual')) {
    function bb_banco_atual()
    {
        global $bb_tenant_db_atual;
        if (!empty($bb_tenant_db_atual)) {
            return $bb_tenant_db_atual;
        }
        return bb_banco_controle();
    }
}

if (!function_exists('bb_credenciais_banco')) {
    // Mesmas regras de conexao do sistema (inclui MYSQL_URL / DATABASE_URL)
    function bb_credenciais_banco()
    {
        static $cred = null;
        if ($cred !== null) {
            return $cred;
        }

        $servidor = getenv('DB_HOST') ?: 'localhost';
        $porta    = getenv('DB_PORT') ?: '3306';
        $usuario  = getenv('DB_USER') ?: 'root';
        $senha    = getenv('DB_PASSWORD') ?: '';
        $banco    = getenv('DB_NAME') ?: 'barbearia';

        $url_bd = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
        if ($url_bd) {
            $partes = parse_url($url_bd);
            if ($partes && isset($partes['host'])) {
                $servidor = $partes['host'];
                $porta    = isset($partes['port']) ? $partes['port'] : '3306';
                $usuario  = isset($partes['user']) ? $partes['user'] : $usuario;
                $senha    = isset($partes['pass']) ? rawurldecode($partes['pass']) : '';
                $banco    = isset($partes['path']) ? ltrim($partes['path'], '/') : $banco;
            }
        }

        $cred = array(
            'host'    => $servidor,
            'porta'   => $porta,
            'usuario' => $usuario,
            'senha'   => $senha,
            'banco'   => $banco,
        );
        return $cred;
    }
}

if (!function_exists('bb_pdo_banco')) {
    // Abre uma conexao (com erros em excecao) para um banco qualquer
    function bb_pdo_banco($banco)
    {
        $cred = bb_credenciais_banco();
        $pdo = new PDO(
            'mysql:dbname=' . $banco . ';host=' . $cred['host'] . ';port=' . $cred['porta'] . ';charset=utf8',
            $cred['usuario'],
            $cred['senha']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
}

if (!function_exists('bb_requisicao_de_controle')) {
    // Requisicoes que sempre usam o banco de controle (SaaS)
    function bb_requisicao_de_controle($caminho)
    {
        $caminho = (string) $caminho;
        if ($caminho === '') {
            return PHP_SAPI === 'cli';
        }
        if (strpos($caminho, '/superadmin/') !== false || substr($caminho, -12) === '/superadmin') {
            return true;
        }
        $arquivo = basename($caminho);
        // Assinatura e pagina de planos sao do SaaS (banco de controle),
        // nunca da demonstracao nem do banco de um cliente.
        if (preg_match('/^(assinar(-[a-z0-9-]+)?|planos)\.php$/i', $arquivo)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('bb_host_subdominio_base')) {
    // Retorna o slug quando o host e {slug}.dominio-base (senao '')
    function bb_host_subdominio_base($host)
    {
        $host = bb_normalizar_host($host);
        $base = '.' . strtolower(BB_DOMINIO_BASE);
        if ($host === '' || substr($host, -strlen($base)) !== $base) {
            return '';
        }
        $slug = substr($host, 0, -strlen($base));
        return ($slug !== '' && strpos($slug, '.') === false) ? $slug : '';
    }
}

if (!function_exists('bb_host_controle')) {
    // Hosts autorizados a usar o banco de controle (instalacao principal).
    // Qualquer outro host so pode servir se estiver cadastrado em tenant_dominios.
    function bb_host_controle($host)
    {
        $host = bb_normalizar_host($host);
        if ($host === '') {
            return true; // CLI, cron, sem host
        }
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }
        $extra = getenv('BB_HOSTS_CONTROLE');
        if ($extra !== false && $extra !== '') {
            foreach (explode(',', $extra) as $h) {
                if (bb_normalizar_host(trim($h)) === $host) {
                    return true;
                }
            }
        }
        $base = strtolower(BB_DOMINIO_BASE);
        return ($host === $base || $host === 'www.' . $base);
    }
}

if (!function_exists('bb_tenant_resolver')) {
    // Descobre o tenant do dominio. Recebe a conexao com o banco de controle.
    function bb_tenant_resolver($pdo_controle, $host)
    {
        static $cache = array();

        $host = bb_normalizar_host($host);
        if ($host === '' || !$pdo_controle) {
            return null;
        }
        if (array_key_exists($host, $cache)) {
            return $cache[$host];
        }

        $tenant = null;

        try {
            // Dominio proprio so serve depois de verificado (verificado_em).
            // Subdominio da plataforma continua valendo sem essa checagem.
            $q = $pdo_controle->prepare("SELECT t.* FROM tenant_dominios d
                INNER JOIN tenants t ON t.id = d.tenant_id
                WHERE LOWER(d.hostname) = :host
                  AND (d.tipo <> 'proprio' OR d.verificado_em IS NOT NULL)
                LIMIT 1");
            $q->bindValue(':host', $host);
            $q->execute();
            $linha = $q->fetch(PDO::FETCH_ASSOC);
            if ($linha) {
                $tenant = $linha;
            }
        } catch (Exception $e) {
            $tenant = null;
        }

        // Fallback: subdominio direto {slug}.dominio-base
        if (!$tenant) {
            $slug = bb_host_subdominio_base($host);
            if ($slug !== '') {
                try {
                    $q = $pdo_controle->prepare("SELECT * FROM tenants WHERE slug = :slug LIMIT 1");
                    $q->bindValue(':slug', $slug);
                    $q->execute();
                    $linha = $q->fetch(PDO::FETCH_ASSOC);
                    if ($linha) {
                        $tenant = $linha;
                    }
                } catch (Exception $e) {
                    $tenant = null;
                }
            }
        }

        $cache[$host] = $tenant;
        return $tenant;
    }
}

if (!function_exists('bb_tenant_garantir_colunas')) {
    // Garante as colunas de controle do SaaS em tenants.
    // Roda apenas em telas administrativas (nunca no site do cliente).
    function bb_tenant_garantir_colunas($pdo_controle)
    {
        static $feito = false;
        if ($feito) {
            return;
        }
        try {
            $colunas = array(
                'db_name'        => "VARCHAR(64) NULL DEFAULT NULL",
                'site_demo'      => "TINYINT(1) NOT NULL DEFAULT 0",
                'boas_vindas_em' => "DATETIME NULL DEFAULT NULL",
                'schema_version' => "INT UNSIGNED NOT NULL DEFAULT 0",
            );
            foreach ($colunas as $coluna => $definicao) {
                // Identificador de whitelist estrita: nomes e tipos vem do mapa
                // literal acima, mas validamos antes de montar o DDL.
                if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $coluna)) {
                    continue;
                }
                $q = $pdo_controle->query("SHOW COLUMNS FROM tenants LIKE '" . $coluna . "'");
                if (!$q || !$q->fetch()) {
                    $pdo_controle->exec("ALTER TABLE tenants ADD COLUMN " . $coluna . " " . $definicao);
                }
            }
            $feito = true;
        } catch (Exception $e) {
            // Tabela ainda nao existe: nada a fazer
        }
    }
}

if (!function_exists('bb_tenant_provisionar')) {
    // Cria (ou atualiza) o banco de dados do tenant e o usuario admin.
    // Idempotente: pode ser chamada varias vezes sem duplicar dados.
    function bb_tenant_provisionar($pdo_controle, $tenant_id)
    {
        $resultado = array('ok' => false, 'db' => '', 'erro' => '', 'versao' => 0);

        $tenant_id = (int) $tenant_id;
        if ($tenant_id <= 0) {
            $resultado['erro'] = 'estabelecimento invalido';
            return $resultado;
        }
        if (!$pdo_controle) {
            $resultado['erro'] = 'conexao de controle indisponivel';
            return $resultado;
        }

        bb_tenant_garantir_colunas($pdo_controle);

        try {
            $q = $pdo_controle->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
            $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            $q->execute();
            $tenant = $q->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $resultado['erro'] = 'nao foi possivel ler o estabelecimento';
            return $resultado;
        }
        if (!$tenant) {
            $resultado['erro'] = 'estabelecimento nao encontrado';
            return $resultado;
        }

        try {
            $db_controle = (string) $pdo_controle->query('SELECT DATABASE()')->fetchColumn();
            if ($db_controle === '') {
                $resultado['erro'] = 'banco de controle desconhecido';
                return $resultado;
            }

            $db_nome = trim((string) @$tenant['db_name']);
            if ($db_nome === '') {
                $db_nome = bb_tenant_nome_banco($tenant_id);
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $db_nome)) {
                $resultado['erro'] = 'nome de banco invalido';
                return $resultado;
            }
            $resultado['db'] = $db_nome;

            if ($db_nome === $db_controle) {
                // Instalacao principal: os dados ja vivem no banco de controle,
                // mas as migracoes continuam valendo para a estrutura dele.
                $migracao = bb_migracoes_sincronizar($pdo_controle);
                if (!$migracao['ok']) {
                    $resultado['erro'] = 'estrutura do banco principal: ' . $migracao['erro'];
                    return $resultado;
                }
                $pdo_controle->prepare("UPDATE tenants SET db_name = :db, schema_version = :versao WHERE id = :id")
                    ->execute(array(':db' => $db_nome, ':versao' => (int) $migracao['versao'], ':id' => $tenant_id));
                $resultado['ok'] = true;
                $resultado['versao'] = (int) $migracao['versao'];
                return $resultado;
            }

            $pdo_controle->exec("CREATE DATABASE IF NOT EXISTS `$db_nome` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

            $pdo_tenant = bb_pdo_banco($db_nome);

            // Estrutura padrao versionada em git (sql/tenant_schema.sql),
            // nunca mais clonada do banco de um cliente real.
            $schema = bb_tenant_schema_sql();
            if (trim($schema) !== '') {
                $criacao = bb_tenant_criar_schema($pdo_tenant, $schema);
                if (!$criacao['ok']) {
                    $resultado['erro'] = 'nao foi possivel criar a estrutura: ' . $criacao['erro'];
                    return $resultado;
                }
            } else {
                // Reserva: sem o arquivo de schema, clona a estrutura atual
                $tabelas_controle = array('super_admins', 'saas_planos', 'tenants', 'tenant_dominios', 'saas_pagamentos');
                $tabelas = $pdo_controle->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tabelas as $tabela) {
                    if (in_array($tabela, $tabelas_controle, true)) {
                        continue;
                    }
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabela)) {
                        continue;
                    }
                    $pdo_tenant->exec("CREATE TABLE IF NOT EXISTS `$tabela` LIKE `$db_controle`.`$tabela`");
                }
            }

            // Mudancas incrementais para bancos criados antes desta versao
            $migracao = bb_migracoes_sincronizar($pdo_tenant);
            if (!$migracao['ok']) {
                $resultado['erro'] = 'estrutura criada, mas a migracao falhou: ' . $migracao['erro'];
                return $resultado;
            }

            // Configuracao inicial do estabelecimento
            // Nasce com dados neutros do tema: NUNCA clona a config do banco de
            // controle, que pertence a outro estabelecimento (nome, imagens,
            // endereco, mapa, instagram e textos).
            $total_cfg = (int) $pdo_tenant->query('SELECT COUNT(*) FROM config')->fetchColumn();
            if ($total_cfg === 0) {
                $segmento_novo = (isset($tenant['segmento']) && $tenant['segmento'] === 'salao') ? 'salao' : 'barbearia';
                $cfg = array(
                    'nome'               => substr((string) $tenant['nome'], 0, 50),
                    'email'              => substr((string) (isset($tenant['email']) ? $tenant['email'] : ''), 0, 50),
                    'telefone_fixo'      => '',
                    'telefone_whatsapp'  => substr((string) (isset($tenant['telefone']) ? $tenant['telefone'] : ''), 0, 20),
                    'endereco'           => '',
                    'logo'               => 'logo.png',
                    'icone'              => 'favicon.png',
                    'logo_rel'           => 'logo_rel.jpg',
                    'tipo_rel'           => 'pdf',
                    'instagram'          => '',
                    'tipo_comissao'      => 'Porcentagem',
                    'texto_rodape'       => 'Edite este texto nas configuracoes do painel administrativo',
                    'img_banner_index'   => ($segmento_novo === 'salao') ? 'salao-banner.jpg' : 'banner.jpg',
                    'texto_sobre'        => 'Edite este texto nas configuracoes do painel administrativo',
                    'imagem_sobre'       => ($segmento_novo === 'salao') ? 'salao-sobre.jpg' : 'foto_barbearia.jpg',
                    'icone_site'         => ($segmento_novo === 'salao') ? 'salao-favicon.png' : 'favicon.png',
                    'mapa'               => '',
                    'texto_fidelidade'   => 'A cada 10 servicos, ganhe 1!',
                    'quantidade_cartoes' => 10,
                    'texto_agendamento'  => 'Selecione o Prestador',
                    'msg_agendamento'    => 'Sim',
                    'segmento'           => $segmento_novo,
                );

                $colunas = array();
                $marcadores = array();
                $valores = array();
                foreach ($cfg as $coluna => $valor) {
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', $coluna)) {
                        continue;
                    }
                    $colunas[] = '`' . $coluna . '`';
                    $marcadores[] = '?';
                    $valores[] = $valor;
                }
                if (count($colunas) > 0) {
                    $pdo_tenant->prepare('INSERT INTO config (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $marcadores) . ')')
                        ->execute($valores);
                }
            }

            // Usuario administrador do cliente (senha criada no cadastro)
            $senha_crip = isset($tenant['senha_crip']) ? (string) $tenant['senha_crip'] : '';
            if ($senha_crip !== '') {
                $ins = $pdo_tenant->prepare("INSERT INTO usuarios
                    (nome, email, cpf, senha, senha_crip, nivel, data, ativo, foto, atendimento, segmento)
                    SELECT :nome, :email, '', '', :hash, 'Administrador', CURDATE(), 'Sim', 'sem-foto.jpg', 'Sim', ''
                    FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE nivel = 'Administrador')");
                $ins->bindValue(':nome', $tenant['nome']);
                $ins->bindValue(':email', $tenant['email'] ? $tenant['email'] : '');
                $ins->bindValue(':hash', $senha_crip);
                $ins->execute();
            }

            // Cargo padrao usado nas permissoes
            $pdo_tenant->exec("INSERT INTO cargos (nome)
                SELECT 'Administrador' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cargos)");

            $pdo_controle->prepare("UPDATE tenants SET db_name = :db, schema_version = :versao WHERE id = :id")
                ->execute(array(':db' => $db_nome, ':versao' => (int) $migracao['versao'], ':id' => $tenant_id));

            $resultado['ok'] = true;
            $resultado['versao'] = (int) $migracao['versao'];
            return $resultado;
        } catch (Exception $e) {
            $resultado['erro'] = $e->getMessage();
            return $resultado;
        }
    }
}

if (!function_exists('bb_tenant_bloqueio')) {
    // Mensagem de bloqueio do painel do cliente (vazio = liberado)
    function bb_tenant_bloqueio()
    {
        global $tenant_atual;
        if (!$tenant_atual) {
            return '';
        }

        // suspenso/cancelado ja respondem 503 na conexao
        $status = isset($tenant_atual['status']) ? (string) $tenant_atual['status'] : '';
        if ($status === 'pendente') {
            return 'Sua assinatura ainda nao foi confirmada. Finalize o pagamento para liberar o acesso.';
        }

        $venc = isset($tenant_atual['vencimento']) ? (string) $tenant_atual['vencimento'] : '';
        if ($status === 'ativo' && $venc !== '' && $venc !== '0000-00-00') {
            $limite = date('Y-m-d', strtotime($venc . ' +5 days'));
            if (date('Y-m-d') > $limite) {
                return 'A assinatura deste estabelecimento esta vencida. Regularize o pagamento para continuar.';
            }
        }

        return '';
    }
}

if (!function_exists('bb_cainfo')) {
    // Bundle de CAs para chamadas HTTPS externas (Windows/local nao tem por padrao)
    function bb_cainfo()
    {
        static $caminho = null;
        if ($caminho !== null) {
            return $caminho;
        }

        $candidatos = array(
            getenv('SSL_CAINFO') ? getenv('SSL_CAINFO') : '',
            ini_get('openssl.cafile') ? ini_get('openssl.cafile') : '',
            dirname(__DIR__, 2) . '/.secrets/cacert.pem',
            'C:/Program Files/Git/mingw64/ssl/certs/ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
        );

        $caminho = '';
        foreach ($candidatos as $c) {
            if ($c !== '' && is_readable($c)) {
                $caminho = $c;
                break;
            }
        }
        return $caminho;
    }
}

if (!function_exists('bb_arquivo_tenant')) {
    // Nome de arquivo exclusivo do tenant para uploads de marca
    // (logo, icone, banner). Na instalacao principal nada muda.
    function bb_arquivo_tenant($nome)
    {
        $nome = (string) $nome;
        if ($nome === '') {
            return $nome;
        }
        $banco = bb_banco_atual();
        if ($banco === bb_banco_controle()) {
            return $nome;
        }
        $sufixo = preg_replace('/\D+/', '', $banco);
        $sufixo = $sufixo !== '' ? $sufixo : substr(md5($banco), 0, 4);
        return 't' . $sufixo . '-' . ltrim($nome, '-');
    }
}

if (!function_exists('bb_tenant_site_indisponivel')) {
    // Site publico fora do ar para estabelecimentos suspensos ou cancelados
    function bb_tenant_site_indisponivel()
    {
        global $tenant_atual;
        if (!$tenant_atual) {
            return false;
        }
        $status = isset($tenant_atual['status']) ? strtolower(trim((string) $tenant_atual['status'])) : '';
        return in_array($status, array('suspenso', 'cancelado', 'cancelada', 'inativo'), true);
    }
}

if (!function_exists('bb_tenant_responder_indisponivel')) {
    // Pagina 503 unica para o site e o painel do estabelecimento bloqueado
    function bb_tenant_responder_indisponivel()
    {
        global $tenant_atual;

        $nome = isset($tenant_atual['nome']) ? trim((string) $tenant_atual['nome']) : '';
        if ($nome === '') {
            $nome = 'Este estabelecimento';
        }

        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            header('Retry-After: 86400');
            header('Cache-Control: no-store, max-age=0');
        }

        $nome_escapado = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<meta name="robots" content="noindex">';
        echo '<title>' . $nome_escapado . ' · temporariamente indisponível</title>';
        echo '<style>
            *{box-sizing:border-box}
            body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
                 background:#0f1115;color:#f2f3f5;font-family:Poppins,-apple-system,Segoe UI,Roboto,sans-serif;padding:24px}
            .card{max-width:520px;width:100%;background:#171a21;border:1px solid #2a2f3a;border-radius:16px;
                  padding:34px 28px;text-align:center}
            .icone{font-size:34px;line-height:1;margin-bottom:14px}
            h1{font-size:21px;margin:0 0 10px}
            p{margin:0 0 10px;color:#a9b0bd;font-size:14px;line-height:1.6}
            .nome{color:#f2f3f5;font-weight:600}
            small{display:block;margin-top:18px;color:#6f7786;font-size:12px}
        </style></head><body><main class="card">';
        echo '<div class="icone">🚧</div>';
        echo '<h1>Site temporariamente indisponível</h1>';
        echo '<p><span class="nome">' . $nome_escapado . '</span> está com o acesso suspenso no momento.</p>';
        echo '<p>Se você é o responsável pelo estabelecimento, regularize a assinatura para liberar o site e o painel.</p>';
        echo '<small>Se você é cliente, tente novamente mais tarde.</small>';
        echo '</main></body></html>';
        exit;
    }
}

// ============================================================
// SLUG DO SUBDOMINIO (autocadastro e super admin)
// ------------------------------------------------------------
// Um slug vira rotulo DNS: {slug}.{BB_DOMINIO_BASE}. Ele precisa ser
// valido como rotulo, nao pode ser nome reservado do sistema e nao pode
// colidir com outro estabelecimento nem com um host ja registrado.
// ============================================================
if (!function_exists('bb_slug_reservados')) {
    function bb_slug_reservados()
    {
        $lista = array(
            // infraestrutura e e-mail
            'www', 'www2', 'mail', 'email', 'emails', 'smtp', 'imap', 'pop', 'pop3', 'webmail', 'mx',
            'ftp', 'sftp', 'dns', 'ns', 'ns1', 'ns2', 'ns3', 'autodiscover', 'autoconfig', 'localhost',
            'root', 'srv', 'vpn', 'proxy', 'gateway', 'edge', 'cdn', 'static', 'assets', 'img', 'images', 'media',
            // aplicacao e paines internos
            'api', 'apis', 'app', 'apps', 'admin', 'administrador', 'superadmin', 'sistema', 'painel',
            'painel-admin', 'login', 'logout', 'cadastro', 'cadastrar', 'registrar', 'conta', 'contas',
            // comercial, cobranca e suporte
            'assinar', 'assinatura', 'assinaturas', 'pagamento', 'pagamentos', 'checkout', 'cobranca',
            'cobrancas', 'fatura', 'faturas', 'billing', 'plano', 'planos', 'saas', 'webhook', 'webhooks',
            'asaas', 'suporte', 'ajuda', 'help', 'docs', 'status', 'blog', 'loja', 'store', 'crm', 'erp',
            // ambientes de teste
            'dev', 'test', 'teste', 'tests', 'staging', 'homolog', 'homologacao', 'demo', 'sandbox'
        );
        // O rotulo do proprio dominio base tambem fica fora
        // (ex.: iluminatto.iluminatto.online nao pode existir).
        $base = strtolower((string) BB_DOMINIO_BASE);
        $rotulo_base = strtolower(trim(explode('.', $base)[0]));
        if ($rotulo_base !== '') {
            $lista[] = $rotulo_base;
        }
        return $lista;
    }
}

if (!function_exists('bb_slug_formato_ok')) {
    // Rotulo DNS valido: 3 a 40 caracteres, letras/numeros/hifen,
    // sem comecar nem terminar com hifen.
    function bb_slug_formato_ok($slug)
    {
        $slug = strtolower(trim((string) $slug));
        if (strlen($slug) < 3 || strlen($slug) > 40) {
            return false;
        }
        return (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $slug);
    }
}

if (!function_exists('bb_slug_avaliar')) {
    // Regra unica do slug: devolve estado + mensagem.
    // Estados: livre, curto, longo, formato, reservado, em_uso, indisponivel.
    // $tenant_id ignora o proprio estabelecimento (edicao no super admin).
    function bb_slug_avaliar($pdo, $slug, $tenant_id = 0)
    {
        $slug = strtolower(trim((string) $slug));
        $tenant_id = (int) $tenant_id;
        $base = strtolower((string) BB_DOMINIO_BASE);

        $resultado = array(
            'slug'   => $slug,
            'host'   => $slug !== '' ? $slug . '.' . $base : '',
            'estado' => 'livre',
            'motivo' => '',
        );

        if ($slug === '' || strlen($slug) < 3) {
            $resultado['estado'] = 'curto';
            $resultado['motivo'] = 'O endereço do site precisa ter ao menos 3 caracteres.';
            return $resultado;
        }
        if (strlen($slug) > 40) {
            $resultado['estado'] = 'longo';
            $resultado['motivo'] = 'O endereço do site pode ter no máximo 40 caracteres.';
            return $resultado;
        }
        if (!bb_slug_formato_ok($slug)) {
            $resultado['estado'] = 'formato';
            $resultado['motivo'] = 'Use apenas letras, números e hífen, começando e terminando com letra ou número.';
            return $resultado;
        }
        if (in_array($slug, bb_slug_reservados(), true)) {
            $resultado['estado'] = 'reservado';
            $resultado['motivo'] = 'Este endereço é reservado. Escolha outro.';
            return $resultado;
        }
        if (!($pdo instanceof PDO)) {
            $resultado['estado'] = 'indisponivel';
            $resultado['motivo'] = 'Não foi possível validar o endereço agora. Tente novamente.';
            return $resultado;
        }

        try {
            $q = $pdo->prepare("SELECT id FROM tenants WHERE slug = :slug AND id <> :id LIMIT 1");
            $q->bindValue(':slug', $slug);
            $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            $q->execute();
            if ($q->fetchColumn()) {
                $resultado['estado'] = 'em_uso';
                $resultado['motivo'] = 'Este endereço já está em uso. Escolha outro.';
                return $resultado;
            }

            // Host ja registrado (subdominio antigo ou dominio proprio) de outro cliente
            $q = $pdo->prepare("SELECT tenant_id FROM tenant_dominios WHERE hostname = :host LIMIT 1");
            $q->bindValue(':host', $resultado['host']);
            $q->execute();
            $dono = $q->fetchColumn();
            if ($dono !== false && $dono !== null && (int) $dono !== $tenant_id) {
                $resultado['estado'] = 'em_uso';
                $resultado['motivo'] = 'Este endereço já está em uso. Escolha outro.';
                return $resultado;
            }
        } catch (Exception $e) {
            $resultado['estado'] = 'indisponivel';
            $resultado['motivo'] = 'Não foi possível validar o endereço agora. Tente novamente.';
            return $resultado;
        }

        return $resultado;
    }
}

if (!function_exists('bb_slug_motivo')) {
    // Devolve '' quando o slug pode ser usado, ou a mensagem do motivo.
    function bb_slug_motivo($pdo, $slug, $tenant_id = 0)
    {
        $avaliacao = bb_slug_avaliar($pdo, $slug, $tenant_id);
        return $avaliacao['motivo'];
    }
}

// Dominio proprio: registro, verificacao TXT/CNAME e regra de servico
require_once(__DIR__ . '/dominio.php');
