<?php 

// ============================================================
// CONEXAO COM O BANCO DE DADOS - Barbearia PRO
// ------------------------------------------------------------
// Le as configuracoes por variaveis de ambiente (Docker/Railway)
// com fallback para ambiente local (XAMPP).
//
// Variaveis suportadas:
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
//   MYSQL_URL / DATABASE_URL (ex.: mysql://user:senha@host:3306/banco)
//   APP_BASE_PATH            (caminho base da aplicacao; "" = raiz)
//   SHOW_DB_ERROR            ("1" para exibir detalhes do erro)
// ============================================================

$banco    = getenv('DB_NAME') ?: 'barbearia';
$usuario  = getenv('DB_USER') ?: 'root';
$senha    = getenv('DB_PASSWORD') ?: '';
$servidor = getenv('DB_HOST') ?: 'localhost';
$porta    = getenv('DB_PORT') ?: '3306';

// Suporte a URL de conexao (formato usado por Railway / servicos gerenciados)
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

// URL base do sistema (usada em links e redirecionamentos)
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host_url  = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$base_path_env = getenv('APP_BASE_PATH');
$base_path = trim((string) $base_path_env, '/');

if ($base_path_env === false) {
    // APP_BASE_PATH nao definido -> deteccao automatica p/ XAMPP (htdocs/barbearia)
    $host_sem_porta = preg_replace('/:\d+$/', '', $host_url);
    if (in_array($host_sem_porta, array('localhost', '127.0.0.1'))) {
        $base_path = 'barbearia';
    }
}
$url_sistema = $scheme . '://' . $host_url . '/' . ($base_path !== '' ? $base_path . '/' : '');

// ============================================================
// SENHAS
// ------------------------------------------------------------
// Usa password_hash (bcrypt/argon2). Bancos antigos tem senha em
// MD5: bb_checar_senha valida o MD5 e avisa que precisa regravar o
// hash, migrando o usuario no proximo login.
// ============================================================
if (!function_exists('bb_hash_senha')) {
    function bb_hash_senha($senha)
    {
        return password_hash((string) $senha, PASSWORD_DEFAULT);
    }
}

if (!function_exists('bb_checar_senha')) {
    // Retorna array('ok' => bool, 'rehash' => bool)
    function bb_checar_senha($senha, $hash)
    {
        $hash = (string) $hash;
        if ($hash === '') {
            return array('ok' => false, 'rehash' => false);
        }

        // Hash legado (MD5 de 32 caracteres hexadecimais)
        if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
            $ok = hash_equals(strtolower($hash), md5((string) $senha));
            return array('ok' => $ok, 'rehash' => $ok);
        }

        $ok = password_verify((string) $senha, $hash);
        return array('ok' => $ok, 'rehash' => $ok && password_needs_rehash($hash, PASSWORD_DEFAULT));
    }
}

if (!function_exists('bb_permitir_usuarios_teste')) {
    // Permite criar contas de teste (admin/admin, admin@admin) apenas em
    // ambiente local/dev. Em producao (DB remoto ou SEED_TEST_USERS=0) fica
    // desativado por seguranca. Override explicito via env SEED_TEST_USERS.
    function bb_permitir_usuarios_teste()
    {
        $flag = getenv('SEED_TEST_USERS');
        if ($flag !== false && $flag !== '') {
            $v = strtolower(trim((string) $flag));
            return in_array($v, array('1', 'true', 'yes', 'on'), true);
        }
        // Auto-detect local dev: apenas quando a requisicao vem de localhost
        // (HTTP_HOST e REMOTE_ADDR locais). Producao (dominio/IP externo) nao
        // semeia contas de teste mesmo com DB local (ex.: VPS).
        $http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        if ($http_host === '' || $remote === '') {
            return false; // CLI / sem contexto HTTP: exige flag explicita
        }
        $http_host = strtolower(trim(explode(':', $http_host)[0]));
        $local_hosts = array('localhost', '127.0.0.1', '::1');
        return in_array($http_host, $local_hosts, true)
            && in_array($remote, $local_hosts, true);
    }
}

date_default_timezone_set('America/Sao_Paulo');

// ============================================================
// MULTI-TENANT
// ------------------------------------------------------------
// O banco de controle guarda os estabelecimentos e assinaturas.
// Quando o dominio acessado pertence a um cliente, a conexao e
// trocada para o banco de dados daquele cliente.
// ============================================================
require_once(__DIR__ . '/tenant.php');

$bb_banco_controle = $banco;
$bb_tenant_db_atual = $banco;
$tenant_atual = null;
$tenant_id_atual = 0;

try {
    $pdo = new PDO("mysql:dbname=$banco;host=$servidor;port=$porta;charset=utf8", "$usuario", "$senha");
} catch (Exception $e) {
    if (getenv('SHOW_DB_ERROR') === '1') {
        echo 'Nao conectado ao Banco de Dados! <br><br>' . $e->getMessage();
    } else {
        echo 'Nao foi possivel conectar ao banco de dados. Verifique as configuracoes de conexao e se o banco foi importado.';
    }
    exit;
}

$pdo_controle = $pdo;

$bb_caminho_req = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
$bb_host_req = bb_host_atual();

if (bb_requisicao_de_controle($bb_caminho_req)) {
    // Painel SaaS e autocadastro existem somente no dominio principal
    $bb_tenant_host = bb_tenant_resolver($pdo_controle, $bb_host_req);
    if ($bb_tenant_host && bb_tenant_banco($bb_tenant_host) !== $banco) {
        http_response_code(404);
        echo 'Nao encontrado.';
        exit;
    }
} else {
    $tenant_atual = bb_tenant_resolver($pdo_controle, $bb_host_req);
    if ($tenant_atual) {
        $tenant_id_atual = (int) $tenant_atual['id'];
        $bb_tenant_db_atual = bb_tenant_banco($tenant_atual);
    } elseif (bb_host_subdominio_base($bb_host_req) !== '') {
        // Subdominio sem estabelecimento: nao serve o site principal
        http_response_code(404);
        echo 'Estabelecimento nao encontrado.';
        exit;
    }
}

// Estabelecimento suspenso ou cancelado: site e painel fora do ar
if (bb_tenant_site_indisponivel()) {
    bb_tenant_responder_indisponivel();
}

if ($bb_tenant_db_atual !== $banco) {
    try {
        $pdo = new PDO("mysql:dbname=" . $bb_tenant_db_atual . ";host=$servidor;port=$porta;charset=utf8", "$usuario", "$senha");
    } catch (Exception $e) {
        if (getenv('SHOW_DB_ERROR') === '1') {
            echo 'Nao conectado ao banco de dados do estabelecimento! <br><br>' . $e->getMessage();
        } else {
            echo 'Este estabelecimento ainda nao esta configurado. Fale com o suporte.';
        }
        exit;
    }
}

// VARIAVEIS DO SISTEMA (fallback usado somente na primeira instalacao)
$nome_sistema      = 'Barbearia PRO';
$email_sistema     = 'admin@admin';
$whatsapp_sistema  = '(11) 9999-9999';
$segmento          = 'barbearia';

$query = $pdo->query("SELECT * from config ");
if ($query) {
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total_reg = @count($res);
    if ($total_reg == 0) {
        // Primeira instalacao: cria a config padrao e rele para definir as variaveis
        $pdo->query("INSERT INTO config SET nome = '$nome_sistema', email = '$email_sistema', telefone_whatsapp = '$whatsapp_sistema', logo = 'logo.png', icone = 'favicon.ico', logo_rel = 'logo_rel.jpg', tipo_rel = 'pdf', tipo_comissao = 'Porcentagem', texto_rodape = 'Edite este texto nas configuracoes do painel administrador', img_banner_index = 'banner.jpg', quantidade_cartoes = 10, texto_agendamento = 'Selecionar Prestador de Servico', msg_agendamento = 'Sim'");
        $query = $pdo->query("SELECT * from config ");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        $total_reg = @count($res);
    }
    if ($total_reg > 0) {
        $nome_sistema      = $res[0]['nome'];
        $email_sistema     = $res[0]['email'];
        $whatsapp_sistema  = $res[0]['telefone_whatsapp'];
        $tipo_rel          = $res[0]['tipo_rel'];
        $telefone_fixo_sistema = $res[0]['telefone_fixo'];
        $endereco_sistema  = $res[0]['endereco'];
        $logo_rel          = $res[0]['logo_rel'];
        $logo_sistema      = $res[0]['logo'];
        $icone_sistema     = $res[0]['icone'];
        $instagram_sistema = $res[0]['instagram'];
        $tipo_comissao     = $res[0]['tipo_comissao'];
        $texto_rodape      = $res[0]['texto_rodape'];
        $img_banner_index  = $res[0]['img_banner_index'];
        $icone_site        = $res[0]['icone_site'];
        $texto_sobre       = $res[0]['texto_sobre'];
        $imagem_sobre      = $res[0]['imagem_sobre'];
        $mapa              = $res[0]['mapa'];
        $quantidade_cartoes = $res[0]['quantidade_cartoes'];
        $texto_fidelidade  = $res[0]['texto_fidelidade'];
        $texto_agendamento = $res[0]['texto_agendamento'];
        $msg_agendamento   = $res[0]['msg_agendamento'];
        // Segmento do negocio: 'barbearia' (padrao) ou 'salao' (troca o tema do site)
        $segmento          = (isset($res[0]['segmento']) && $res[0]['segmento'] !== '') ? $res[0]['segmento'] : 'barbearia';

        $tel_whatsapp = '55' . preg_replace('/[ ()-]+/', '', $whatsapp_sistema);
    }
}

// ============================================================
// IDENTIDADE POR SEGMENTO
// ------------------------------------------------------------
// Quando o segmento e 'salao', o sistema troca nome, imagens e
// textos do site pelos dados salvos em config_salao.
// ============================================================
if (!function_exists('bb_dados_salao')) {
    function bb_dados_salao($pdo)
    {
        static $dados = false;
        if ($dados === false) {
            $dados = null;
            try {
                $q = $pdo->query("SELECT * FROM config_salao ORDER BY id LIMIT 1");
                if ($q) {
                    $r = $q->fetchAll(PDO::FETCH_ASSOC);
                    if (@count($r) > 0) {
                        $dados = $r[0];
                    }
                }
            } catch (Exception $e) {
                $dados = null;
            }
        }
        return $dados;
    }
}

if (!function_exists('bb_aplicar_segmento')) {
    function bb_aplicar_segmento($seg)
    {
        global $pdo, $nome_sistema, $img_banner_index, $imagem_sobre, $texto_sobre, $texto_rodape, $texto_agendamento;
        global $whatsapp_sistema, $tel_whatsapp, $endereco_sistema, $instagram_sistema, $icone_site, $logo_sistema, $logo_rel, $texto_fidelidade, $email_sistema;
        global $nome_sistema_base, $img_banner_index_base, $imagem_sobre_base, $texto_sobre_base, $texto_rodape_base, $texto_agendamento_base;
        global $whatsapp_sistema_base, $endereco_sistema_base, $instagram_sistema_base, $icone_site_base, $logo_sistema_base, $logo_rel_base, $texto_fidelidade_base, $email_sistema_base;

        // Sempre parte dos dados principais (barbearia) antes de aplicar o segmento
        if (isset($nome_sistema_base) && $nome_sistema_base !== '') {
            $nome_sistema       = $nome_sistema_base;
            $img_banner_index   = $img_banner_index_base;
            $imagem_sobre       = $imagem_sobre_base;
            $texto_sobre        = $texto_sobre_base;
            $texto_rodape       = $texto_rodape_base;
            $texto_agendamento  = $texto_agendamento_base;
            $whatsapp_sistema   = $whatsapp_sistema_base;
            $tel_whatsapp       = '55' . preg_replace('/[ ()-]+/', '', $whatsapp_sistema);
            $endereco_sistema   = $endereco_sistema_base;
            $instagram_sistema  = $instagram_sistema_base;
            $icone_site         = $icone_site_base;
            $logo_sistema       = $logo_sistema_base;
            $logo_rel           = $logo_rel_base;
            $texto_fidelidade   = $texto_fidelidade_base;
            $email_sistema      = $email_sistema_base;
        }

        if ($seg !== 'salao') {
            return;
        }
        $salao = bb_dados_salao($pdo);
        if (!$salao) {
            return;
        }
        if (!empty($salao['nome']))             $nome_sistema     = $salao['nome'];
        if (!empty($salao['img_banner_index'])) $img_banner_index = $salao['img_banner_index'];
        if (!empty($salao['imagem_sobre']))     $imagem_sobre     = $salao['imagem_sobre'];
        if (!empty($salao['texto_sobre']))      $texto_sobre      = $salao['texto_sobre'];
        if (!empty($salao['texto_rodape']))     $texto_rodape     = $salao['texto_rodape'];
        if (!empty($salao['texto_agendamento'])) $texto_agendamento = $salao['texto_agendamento'];
        if (!empty($salao['whatsapp'])) {
            $whatsapp_sistema = $salao['whatsapp'];
            $tel_whatsapp     = '55' . preg_replace('/[ ()-]+/', '', $whatsapp_sistema);
        }
        if (!empty($salao['endereco']))          $endereco_sistema  = $salao['endereco'];
        if (!empty($salao['instagram']))         $instagram_sistema = $salao['instagram'];
        if (!empty($salao['email']))             $email_sistema     = $salao['email'];
        if (!empty($salao['icone_site']))        $icone_site        = $salao['icone_site'];
        if (!empty($salao['logo']))              $logo_sistema      = $salao['logo'];
        if (!empty($salao['logo_rel']))          $logo_rel          = $salao['logo_rel'];
        if (!empty($salao['texto_fidelidade']))  $texto_fidelidade  = $salao['texto_fidelidade'];
    }
}

// ============================================================
// PROFISSIONAIS POR SEGMENTO
// ------------------------------------------------------------
// Cada profissional pode atender nos dois temas (segmento vazio)
// ou apenas em um deles. O site lista somente os do tema atual.
// ============================================================
if (!function_exists('bb_filtro_profissionais')) {
    function bb_filtro_profissionais($alias = '')
    {
        global $segmento;
        $col = ($alias !== '') ? $alias . '.segmento' : 'segmento';
        $seg = (isset($segmento) && $segmento == 'salao') ? 'salao' : 'barbearia';
        return " AND ($col IS NULL OR $col = '' OR $col = 'ambos' OR $col = '$seg') ";
    }
}

// Guarda os dados do segmento principal (usados no formulario de configuracao)
$nome_sistema_base      = $nome_sistema;
$img_banner_index_base  = $img_banner_index;
$imagem_sobre_base      = $imagem_sobre;
$texto_sobre_base       = $texto_sobre;
$texto_rodape_base      = $texto_rodape;
$texto_agendamento_base = $texto_agendamento;
$whatsapp_sistema_base   = isset($whatsapp_sistema) ? $whatsapp_sistema : '';
$endereco_sistema_base   = isset($endereco_sistema) ? $endereco_sistema : '';
$instagram_sistema_base  = isset($instagram_sistema) ? $instagram_sistema : '';
$icone_site_base         = isset($icone_site) ? $icone_site : '';
$logo_sistema_base       = isset($logo_sistema) ? $logo_sistema : '';
$logo_rel_base           = isset($logo_rel) ? $logo_rel : '';
$texto_fidelidade_base   = isset($texto_fidelidade) ? $texto_fidelidade : '';
$email_sistema_base      = isset($email_sistema) ? $email_sistema : '';

bb_aplicar_segmento($segmento);

?>
