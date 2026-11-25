<?php
// ============================================================
// DOMINIO PROPRIO - verificacao de propriedade
// ------------------------------------------------------------
// Um cliente que quer usar o proprio dominio precisa provar que
// controla o DNS dele. O registro em tenant_dominios (tipo
// 'proprio') nasce com verificado_em NULL e NAO serve o site:
//   - bb_tenant_resolver so resolve host proprio verificado;
//   - o sync do Caddy so gera bloco para host proprio verificado.
//
// A prova e um registro TXT:
//   _beauty-verificacao.<host>   TXT   "beauty-verify=<token>"
// ou um CNAME do proprio host para o alvo de verificacao
// (SA_DOMINIO_CNAME, padrao clientes.<dominio-base>), que tambem
// precisa apontar para esta VPS para o site responder.
//
// O token fica em tenant_dominios.token_verificacao e e criado
// junto com o registro. Domainos e token sao normalizados aqui,
// do mesmo jeito que o slug do subdominio.
// ============================================================

if (!defined('BB_DOMINIO_CNAME')) {
    $bb_dominio_cname_env = getenv('SA_DOMINIO_CNAME');
    define('BB_DOMINIO_CNAME', ($bb_dominio_cname_env !== false && $bb_dominio_cname_env !== '')
        ? $bb_dominio_cname_env
        : 'clientes.' . BB_DOMINIO_BASE);
}

if (!defined('BB_DOMINIO_TXT_PREFIXO')) {
    define('BB_DOMINIO_TXT_PREFIXO', '_beauty-verificacao');
}

if (!defined('BB_DOMINIO_TXT_VALOR')) {
    define('BB_DOMINIO_TXT_VALOR', 'beauty-verify=');
}

if (!function_exists('bb_dominio_garantir_colunas')) {
    // Colunas novas em tenant_dominios (a migracao 005 faz o mesmo;
    // aqui garante que a tela do super admin nao quebre antes dela).
    function bb_dominio_garantir_colunas($pdo)
    {
        static $feito = false;
        if ($feito || !($pdo instanceof PDO)) {
            return;
        }
        try {
            $colunas = array(
                'token_verificacao' => 'VARCHAR(64) NULL DEFAULT NULL',
                'registrado_em'     => 'DATETIME NULL DEFAULT NULL',
            );
            foreach ($colunas as $coluna => $definicao) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $coluna)) {
                    continue;
                }
                $q = $pdo->query("SHOW COLUMNS FROM tenant_dominios LIKE '" . $coluna . "'");
                if (!$q || !$q->fetch()) {
                    $pdo->exec('ALTER TABLE tenant_dominios ADD COLUMN ' . $coluna . ' ' . $definicao);
                }
            }
            $feito = true;
        } catch (Exception $e) {
            // Tabela ainda nao existe: nada a fazer
        }
    }
}

if (!function_exists('bb_dominio_normalizar')) {
    // Aceita o que o cliente cola (URL, com barra, porta, maiusculas)
    // e devolve so o host. Diferente de bb_normalizar_host, mantem o
    // "www." porque o dominio proprio e cadastrado como o cliente usa.
    function bb_dominio_normalizar($host)
    {
        $host = trim((string) $host);
        if ($host === '') {
            return '';
        }
        $host = preg_replace('~^[a-z][a-z0-9+.-]*://~i', '', $host);
        $host = (string) preg_split('~[/?#]~', $host, 2)[0];
        $host = preg_replace('/:\d+$/', '', $host);
        return rtrim(strtolower($host), '.');
    }
}

if (!function_exists('bb_dominio_formato_ok')) {
    // Hostname valido: rotulos de 1 a 63 caracteres, letras/numeros/
    // hifen, sem comecar nem terminar com hifen, com ponto e TLD alfabetico.
    function bb_dominio_formato_ok($host)
    {
        $host = (string) $host;
        if (strlen($host) < 4 || strlen($host) > 253) {
            return false;
        }
        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/', $host)) {
            return false;
        }
        $rotulos = explode('.', $host);
        $tld = end($rotulos);
        return (bool) preg_match('/^[a-z]{2,63}$/', $tld);
    }
}

if (!function_exists('bb_dominio_avaliar')) {
    // Regra unica do dominio proprio: devolve estado + mensagem.
    // Estados: livre, curto, formato, longo, reservado, em_uso, indisponivel.
    // $tenant_id ignora o proprio estabelecimento (revalidação).
    function bb_dominio_avaliar($pdo, $host, $tenant_id = 0)
    {
        $host = bb_dominio_normalizar($host);
        $tenant_id = (int) $tenant_id;
        $base = strtolower((string) BB_DOMINIO_BASE);

        $resultado = array(
            'host'   => $host,
            'estado' => 'livre',
            'motivo' => '',
        );

        if ($host === '') {
            $resultado['estado'] = 'curto';
            $resultado['motivo'] = 'Informe o domínio completo, por exemplo: barbearia.com.br.';
            return $resultado;
        }
        if (strlen($host) > 253) {
            $resultado['estado'] = 'longo';
            $resultado['motivo'] = 'O domínio pode ter no máximo 253 caracteres.';
            return $resultado;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $resultado['estado'] = 'reservado';
            $resultado['motivo'] = 'Informe um nome de domínio, não um endereço IP.';
            return $resultado;
        }
        if (!bb_dominio_formato_ok($host)) {
            $resultado['estado'] = 'formato';
            $resultado['motivo'] = 'Informe um domínio completo, com apenas letras, números, hífen e pontos (ex.: barbearia.com.br).';
            return $resultado;
        }
        if ($host === 'localhost' || substr($host, -10) === '.localhost') {
            $resultado['estado'] = 'reservado';
            $resultado['motivo'] = 'Este domínio é reservado. Use um domínio que você controla.';
            return $resultado;
        }

        // Dominio-base e subdominios dele sao da plataforma: o cliente
        // usa o subdominio do estabelecimento, nao um dominio proprio.
        if ($host === $base || $host === 'www.' . $base) {
            $resultado['estado'] = 'reservado';
            $resultado['motivo'] = 'Este domínio pertence à plataforma. Use o subdomínio do estabelecimento.';
            return $resultado;
        }
        $sufixo_base = '.' . $base;
        if (substr($host, -strlen($sufixo_base)) === $sufixo_base) {
            $resultado['estado'] = 'reservado';
            $resultado['motivo'] = 'Este domínio pertence à plataforma. Use o subdomínio do estabelecimento.';
            return $resultado;
        }

        if (!($pdo instanceof PDO)) {
            $resultado['estado'] = 'indisponivel';
            $resultado['motivo'] = 'Não foi possível validar o domínio agora. Tente novamente.';
            return $resultado;
        }

        try {
            // Qualquer registro (subdominio ou dominio proprio, verificado
            // ou nao) reserva o hostname para o estabelecimento dono.
            $q = $pdo->prepare("SELECT tenant_id FROM tenant_dominios WHERE hostname = :host LIMIT 1");
            $q->bindValue(':host', $host);
            $q->execute();
            $dono = $q->fetchColumn();
            if ($dono !== false && $dono !== null && (int) $dono !== $tenant_id) {
                $resultado['estado'] = 'em_uso';
                $resultado['motivo'] = 'Este domínio já está em uso por outro estabelecimento.';
                return $resultado;
            }
        } catch (Exception $e) {
            $resultado['estado'] = 'indisponivel';
            $resultado['motivo'] = 'Não foi possível validar o domínio agora. Tente novamente.';
            return $resultado;
        }

        return $resultado;
    }
}

if (!function_exists('bb_dominio_registro_txt')) {
    function bb_dominio_registro_txt($host)
    {
        return BB_DOMINIO_TXT_PREFIXO . '.' . bb_dominio_normalizar($host);
    }
}

if (!function_exists('bb_dominio_txt_valor')) {
    function bb_dominio_txt_valor($token)
    {
        return BB_DOMINIO_TXT_VALOR . (string) $token;
    }
}

if (!function_exists('bb_dominio_cname_alvos')) {
    // Alvos de CNAME aceitos como prova (sem o www., comparados sem ponto final)
    function bb_dominio_cname_alvos($tenant = null)
    {
        $alvos = array(BB_DOMINIO_CNAME, BB_DOMINIO_BASE, 'www.' . BB_DOMINIO_BASE);
        if (is_array($tenant) && !empty($tenant['slug'])) {
            $alvos[] = $tenant['slug'] . '.' . BB_DOMINIO_BASE;
        }
        $limpos = array();
        foreach ($alvos as $alvo) {
            $alvo = rtrim(strtolower(trim((string) $alvo)), '.');
            if ($alvo !== '') {
                $limpos[$alvo] = $alvo;
            }
        }
        return array_values($limpos);
    }
}

if (!function_exists('bb_dominio_registros_esperados')) {
    // Registros DNS que o cliente precisa criar (para exibir na tela)
    function bb_dominio_registros_esperados($host, $token, $tenant = null)
    {
        $host = bb_dominio_normalizar($host);
        $token = (string) $token;
        $alvos = bb_dominio_cname_alvos($tenant);
        return array(
            array(
                'tipo'  => 'TXT',
                'nome'  => bb_dominio_registro_txt($host),
                'valor' => bb_dominio_txt_valor($token),
                'nota'  => 'Prova de propriedade. Basta este registro.',
            ),
            array(
                'tipo'  => 'CNAME',
                'nome'  => $host,
                'valor' => $alvos[0],
                'nota'  => 'Alternativa ao TXT: aponte o domínio para ' . $alvos[0] . '.',
            ),
        );
    }
}

if (!function_exists('bb_dominio_dns_registros')) {
    // Le o DNS do host. Devolve txt (lista de valores) e cname (alvos).
    // Sem rede/DNS, devolve listas vazias (a verificacao fica pendente).
    function bb_dominio_dns_registros($host)
    {
        $saida = array('txt' => array(), 'cname' => array());
        if (!function_exists('dns_get_record')) {
            return $saida;
        }

        $host = bb_dominio_normalizar($host);
        if ($host === '') {
            return $saida;
        }

        $txt = @dns_get_record(bb_dominio_registro_txt($host), DNS_TXT);
        if (is_array($txt)) {
            foreach ($txt as $linha) {
                if (isset($linha['txt'])) {
                    $saida['txt'][] = (string) $linha['txt'];
                } elseif (isset($linha['entries']) && is_array($linha['entries'])) {
                    $saida['txt'][] = implode('', $linha['entries']);
                }
            }
        }

        $cname = @dns_get_record($host, DNS_CNAME);
        if (is_array($cname)) {
            foreach ($cname as $linha) {
                if (!empty($linha['target'])) {
                    $saida['cname'][] = (string) $linha['target'];
                }
            }
        }

        return $saida;
    }
}

if (!function_exists('bb_dominio_checar_registros')) {
    // Decisao pura: o DNS lido prova a propriedade do host?
    function bb_dominio_checar_registros($host, $token, $registros, $alvos = null)
    {
        $esperado = bb_dominio_txt_valor($token);
        $nome_txt = bb_dominio_registro_txt($host);
        if ($alvos === null) {
            $alvos = bb_dominio_cname_alvos();
        }

        $resultado = array(
            'ok'      => false,
            'metodo'  => '',
            'motivo'  => '',
            'txt'     => isset($registros['txt']) ? $registros['txt'] : array(),
            'cname'   => isset($registros['cname']) ? $registros['cname'] : array(),
            'esperado_txt' => $esperado,
            'nome_txt'     => $nome_txt,
            'alvos_cname'  => $alvos,
        );

        if (trim((string) $token) === '') {
            $resultado['motivo'] = 'Este domínio está sem token de verificação. Gere o registro novamente.';
            return $resultado;
        }

        foreach ($resultado['txt'] as $valor) {
            if (trim((string) $valor) === $esperado) {
                $resultado['ok'] = true;
                $resultado['metodo'] = 'txt';
                return $resultado;
            }
        }

        foreach ($resultado['cname'] as $alvo) {
            $alvo = rtrim(strtolower(trim((string) $alvo)), '.');
            if (in_array($alvo, $alvos, true)) {
                $resultado['ok'] = true;
                $resultado['metodo'] = 'cname';
                return $resultado;
            }
        }

        $resultado['motivo'] = 'Ainda não encontramos o registro de verificação. '
            . 'Crie o TXT ' . $nome_txt . ' com o valor ' . $esperado
            . ' (ou um CNAME do domínio para ' . $alvos[0] . ') e aguarde a propagação do DNS.';
        return $resultado;
    }
}

if (!function_exists('bb_dominio_registrar')) {
    // Registra um dominio proprio ainda NAO verificado.
    // Idempotente: se o host ja e deste estabelecimento, mantem o token.
    function bb_dominio_registrar($pdo, $tenant_id, $host)
    {
        $tenant_id = (int) $tenant_id;
        $avaliacao = bb_dominio_avaliar($pdo, $host, $tenant_id);
        $avaliacao['ok'] = false;

        if ($tenant_id <= 0) {
            $avaliacao['estado'] = 'indisponivel';
            $avaliacao['motivo'] = 'Estabelecimento inválido.';
            return $avaliacao;
        }
        if ($avaliacao['estado'] !== 'livre') {
            return $avaliacao;
        }
        if (!($pdo instanceof PDO)) {
            $avaliacao['estado'] = 'indisponivel';
            $avaliacao['motivo'] = 'Não foi possível registrar o domínio agora. Tente novamente.';
            return $avaliacao;
        }

        bb_dominio_garantir_colunas($pdo);
        $host = $avaliacao['host'];

        try {
            $q = $pdo->prepare("SELECT * FROM tenant_dominios WHERE hostname = :host AND tenant_id = :id LIMIT 1");
            $q->bindValue(':host', $host);
            $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            $q->execute();
            $existente = $q->fetch(PDO::FETCH_ASSOC);
            if ($existente) {
                $avaliacao['ok'] = true;
                $avaliacao['estado'] = 'registrado';
                $avaliacao['token'] = isset($existente['token_verificacao']) ? (string) $existente['token_verificacao'] : '';
                $avaliacao['verificado_em'] = $existente['verificado_em'];
                return $avaliacao;
            }

            $token = bin2hex(random_bytes(16));
            $ins = $pdo->prepare("INSERT INTO tenant_dominios
                (hostname, tenant_id, tipo, token_verificacao, verificado_em, registrado_em)
                VALUES (:host, :id, 'proprio', :token, NULL, NOW())");
            $ins->bindValue(':host', $host);
            $ins->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            $ins->bindValue(':token', $token);
            $ins->execute();
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $avaliacao['estado'] = 'em_uso';
                $avaliacao['motivo'] = 'Este domínio acabou de ser registrado por outro estabelecimento.';
                return $avaliacao;
            }
            $avaliacao['estado'] = 'indisponivel';
            $avaliacao['motivo'] = 'Não foi possível registrar o domínio agora. Tente novamente.';
            return $avaliacao;
        } catch (Exception $e) {
            $avaliacao['estado'] = 'indisponivel';
            $avaliacao['motivo'] = 'Não foi possível registrar o domínio agora. Tente novamente.';
            return $avaliacao;
        }

        $avaliacao['ok'] = true;
        $avaliacao['estado'] = 'registrado';
        $avaliacao['token'] = $token;
        $avaliacao['verificado_em'] = null;
        return $avaliacao;
    }
}

if (!function_exists('bb_dominio_verificar')) {
    // Confere o DNS e, quando a prova aparece, grava verificado_em.
    // $registros_dns permite reaproveitar uma leitura (testes/cron).
    function bb_dominio_verificar($pdo, $tenant_id, $host, $registros_dns = null)
    {
        $tenant_id = (int) $tenant_id;
        $host = bb_dominio_normalizar($host);

        $resultado = array(
            'ok'       => false,
            'estado'   => 'erro',
            'host'     => $host,
            'motivo'   => '',
            'metodo'   => '',
            'token'    => '',
            'registros'=> array(),
        );

        if ($tenant_id <= 0 || $host === '' || !($pdo instanceof PDO)) {
            $resultado['motivo'] = 'Informe um estabelecimento e um domínio válidos.';
            return $resultado;
        }

        bb_dominio_garantir_colunas($pdo);

        try {
            $q = $pdo->prepare("SELECT * FROM tenant_dominios
                WHERE hostname = :host AND tenant_id = :id AND tipo = 'proprio' LIMIT 1");
            $q->bindValue(':host', $host);
            $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            $q->execute();
            $registro = $q->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $resultado['motivo'] = 'Não foi possível ler o domínio agora. Tente novamente.';
            return $resultado;
        }

        if (!$registro) {
            $resultado['motivo'] = 'Este domínio não está registrado para o estabelecimento.';
            return $resultado;
        }

        if (!empty($registro['verificado_em'])) {
            $resultado['ok'] = true;
            $resultado['estado'] = 'verificado';
            $resultado['metodo'] = 'anterior';
            $resultado['token'] = (string) $registro['token_verificacao'];
            $resultado['motivo'] = 'Domínio já verificado em ' . $registro['verificado_em'] . '.';
            return $resultado;
        }

        $token = trim((string) $registro['token_verificacao']);
        if ($token === '') {
            // Registro antigo/sem token: cria um agora para nao ficar sem prova
            $token = bin2hex(random_bytes(16));
            try {
                $pdo->prepare("UPDATE tenant_dominios SET token_verificacao = :token WHERE hostname = :host AND tenant_id = :id")
                    ->execute(array(':token' => $token, ':host' => $host, ':id' => $tenant_id));
            } catch (Exception $e) {
                $resultado['motivo'] = 'Não foi possível preparar o token de verificação.';
                return $resultado;
            }
        }
        $resultado['token'] = $token;

        // Dados do estabelecimento para aceitar o CNAME do proprio subdominio
        $tenant = null;
        try {
            $q = $pdo->prepare("SELECT id, slug FROM tenants WHERE id = :id LIMIT 1");
            $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            $q->execute();
            $tenant = $q->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $tenant = null;
        }

        $alvos = bb_dominio_cname_alvos($tenant ? $tenant : null);
        if ($registros_dns === null) {
            $registros_dns = bb_dominio_dns_registros($host);
        }

        $checagem = bb_dominio_checar_registros($host, $token, $registros_dns, $alvos);
        $resultado['registros'] = $registros_dns;
        $resultado['metodo'] = $checagem['metodo'];

        if (!$checagem['ok']) {
            $resultado['estado'] = 'pendente';
            $resultado['motivo'] = $checagem['motivo'];
            return $resultado;
        }

        try {
            $pdo->prepare("UPDATE tenant_dominios SET verificado_em = NOW()
                WHERE hostname = :host AND tenant_id = :id AND verificado_em IS NULL")
                ->execute(array(':host' => $host, ':id' => $tenant_id));
        } catch (Exception $e) {
            $resultado['estado'] = 'pendente';
            $resultado['motivo'] = 'A prova foi encontrada, mas não foi possível salvar a verificação. Tente novamente.';
            return $resultado;
        }

        $resultado['ok'] = true;
        $resultado['estado'] = 'verificado';
        $resultado['motivo'] = 'Domínio verificado. O site passa a responder neste endereço em até um minuto.';
        return $resultado;
    }
}

if (!function_exists('bb_dominio_remover')) {
    // Remove o dominio proprio (o subdominio automatico nao passa por aqui)
    function bb_dominio_remover($pdo, $tenant_id, $host)
    {
        $tenant_id = (int) $tenant_id;
        $host = bb_dominio_normalizar($host);
        if ($tenant_id <= 0 || $host === '' || !($pdo instanceof PDO)) {
            return false;
        }
        try {
            $q = $pdo->prepare("DELETE FROM tenant_dominios
                WHERE hostname = :host AND tenant_id = :id AND tipo = 'proprio'");
            $q->bindValue(':host', $host);
            $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            return $q->execute();
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('bb_dominio_situacao')) {
    // Rotulo/classe do badge na tela do super admin
    function bb_dominio_situacao($linha)
    {
        $tipo = isset($linha['tipo']) ? strtolower(trim((string) $linha['tipo'])) : 'subdominio';
        if ($tipo !== 'proprio') {
            return array('rotulo' => 'Subdomínio automático', 'classe' => 'info', 'verificado' => true);
        }
        if (!empty($linha['verificado_em'])) {
            return array('rotulo' => 'Verificado', 'classe' => 'ok', 'verificado' => true);
        }
        return array('rotulo' => 'Aguardando verificação', 'classe' => 'warn', 'verificado' => false);
    }
}
