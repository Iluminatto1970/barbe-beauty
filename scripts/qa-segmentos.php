<?php
// ============================================================
// QA DE SEGMENTOS (barbearia x salao)
// ------------------------------------------------------------
// Roda a prova de tema de ponta a ponta, do jeito que ela foi feita
// a mao, mas repetivel: provisiona um estabelecimento descartavel
// de SALAO e outro de BARBEARIA (hosts zz-qa-*), confere o HTML que
// o servidor realmente entrega em cada tela e apaga tudo no final.
//
// Confere:
//   site publico .... CSS do tema, textos de produtos do segmento,
//                     produto cadastrado, ausencia do texto do outro
//                     segmento, sem a faixa de troca de tema e sem
//                     o aviso de demonstracao;
//   login ........... stylesheet do segmento;
//   painel .......... stylesheets do segmento e as opcoes do select
//                     "Tema" do profissional (so "Ambos os temas" +
//                     o tema do estabelecimento);
//   fluxo ........... salva um profissional com "Ambos os temas" pelo
//                     painel e confere que ele aparece no agendamento
//                     publico;
//   limpeza ......... bancos, registros do tenant, bloco do Caddy e
//                     host fora do ar.
//
// Uso (na VPS, como root para escrever no Caddy):
//   php /var/www/releases/current/scripts/qa-segmentos.php
//   php /var/www/releases/current/scripts/qa-segmentos.php --fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf
//
// Rodando o arquivo de outro lugar (ex.: /tmp), aponte a raiz da
// aplicacao publicada:
//   php /tmp/qa-segmentos.php --raiz=/var/www/releases/current
//
// Flags:
//   --keep            nao apaga no final (imprime hosts e acesso)
//   --dominio=...     dominio base (padrao iluminatto.online)
//   --espera=120      segundos de espera por certificado/HTTP
//   --fpm-conf=...    arquivo do PHP-FPM com as variaveis do banco
//   --db-env=...      alternativa ao --fpm-conf (arquivo KEY=VALUE)
//
// Saida: uma linha por checagem e um resumo. Codigo de saida 0 so
// quando todas passam; 1 quando alguma falha; 2 quando nem deu para
// montar o cenario.
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    echo 'Nao encontrado.';
    exit;
}

$opcoes = array(
    'raiz'     => dirname(__DIR__),
    'dominio'  => 'iluminatto.online',
    'espera'   => 120,
    'fpm_conf' => '/etc/php/8.4/fpm/pool.d/www.conf',
    'db_env'   => '',
    'keep'     => false,
);

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--keep') {
        $opcoes['keep'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Uso: php scripts/qa-segmentos.php [--keep] [--dominio=HOST] [--espera=SEG]\n";
        echo "     [--fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf] [--db-env=/caminho/db.env]\n";
        echo "     [--raiz=/var/www/releases/current]\n";
        exit(0);
    } elseif (strpos($arg, '--raiz=') === 0) {
        $opcoes['raiz'] = rtrim(substr($arg, 7), '/\\');
    } elseif (strpos($arg, '--dominio=') === 0) {
        $opcoes['dominio'] = trim(substr($arg, 10));
    } elseif (strpos($arg, '--espera=') === 0) {
        $opcoes['espera'] = max(15, (int) substr($arg, 9));
    } elseif (strpos($arg, '--fpm-conf=') === 0) {
        $opcoes['fpm_conf'] = trim(substr($arg, 11));
    } elseif (strpos($arg, '--db-env=') === 0) {
        $opcoes['db_env'] = trim(substr($arg, 9));
    } else {
        fwrite(STDERR, "Opcao desconhecida: $arg\n");
        exit(2);
    }
}

$opcoes['fpm_conf'] = $opcoes['fpm_conf'];
$raiz = $opcoes['raiz'];

// ------------------------------------------------------------
// Ambiente do banco (mesmas variaveis que o site usa)
// ------------------------------------------------------------
function qa_carregar_env($arquivo)
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
        if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
            continue;
        }
        list($chave, $valor) = explode('=', $linha, 2);
        $chave = trim($chave);
        $valor = trim(trim($valor), "\"'");
        if ($chave !== '' && getenv($chave) === false) {
            putenv($chave . '=' . $valor);
        }
    }
    return true;
}

function qa_carregar_fpm($arquivo)
{
    if ($arquivo === '' || !is_readable($arquivo)) {
        return false;
    }
    $cfg = @parse_ini_file($arquivo, false, INI_SCANNER_RAW);
    if (!is_array($cfg) || empty($cfg['env']) || !is_array($cfg['env'])) {
        return false;
    }
    foreach ($cfg['env'] as $chave => $valor) {
        if (getenv($chave) === false) {
            putenv($chave . '=' . $valor);
        }
    }
    return true;
}

if ($opcoes['db_env'] !== '') {
    qa_carregar_env($opcoes['db_env']);
} else {
    qa_carregar_fpm($opcoes['fpm_conf']);
}
if (getenv('DB_USER') === false || getenv('DB_NAME') === false) {
    fwrite(STDERR, "Sem credenciais do banco: use --fpm-conf ou --db-env.\n");
    exit(2);
}

if (!is_file($raiz . '/sistema/tenant.php') || !is_file($raiz . '/sistema/migracoes.php')) {
    fwrite(STDERR, "Raiz da aplicacao invalida: $raiz\n");
    exit(2);
}
require_once($raiz . '/sistema/tenant.php');
require_once($raiz . '/sistema/migracoes.php');

// ------------------------------------------------------------
// Estado global (a limpeza tambem roda em erro fatal)
// ------------------------------------------------------------
$qa_pdo = null;
$qa_controle = '';
$qa_tenants = array();
$qa_hosts = array();
$qa_keep = $opcoes['keep'];
$qa_limpo = false;
$qa_checks_ok = 0;
$qa_checks_falha = 0;
$qa_falhas = array();

function qa_e_root()
{
    if (function_exists('posix_geteuid')) {
        return posix_geteuid() === 0;
    }
    return trim((string) @shell_exec('id -u')) === '0';
}

function qa_sync_caddy()
{
    $gerador = '/usr/local/sbin/beauty-sync-tenants.php';
    if (!qa_e_root()) {
        return false;
    }
    if (is_file($gerador)) {
        @exec('php ' . escapeshellarg($gerador) . ' 2>&1', $saida, $codigo);
    }
    @exec('caddy validate --config /etc/caddy/Caddyfile >/dev/null 2>&1 && systemctl reload caddy 2>&1', $saida2, $codigo2);
    return true;
}

function qa_http($url, $opcoes = array())
{
    $ch = curl_init($url);
    if ($ch === false) {
        return array('status' => 0, 'corpo' => '', 'erro' => 'curl indisponivel');
    }
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'bb-qa-segmentos',
    ));
    if (!empty($opcoes['jar'])) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $opcoes['jar']);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $opcoes['jar']);
    }
    if (!empty($opcoes['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($opcoes['post']));
    }
    if (isset($opcoes['timeout'])) {
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) $opcoes['timeout']);
    }
    $corpo = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);
    return array('status' => $status, 'corpo' => ($corpo === false ? '' : $corpo), 'erro' => $erro);
}

function qa_cookie_jar($jar, $nome)
{
    if (!is_readable($jar)) {
        return '';
    }
    $linhas = @file($jar, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$linhas) {
        return '';
    }
    foreach ($linhas as $linha) {
        if ($linha === '' || $linha[0] === '#') {
            continue;
        }
        $campos = explode("\t", $linha);
        if (count($campos) >= 7 && $campos[5] === $nome) {
            return $campos[6];
        }
    }
    return '';
}

function qa_contem($texto, $trecho)
{
    return mb_stripos((string) $texto, (string) $trecho, 0, 'UTF-8') !== false;
}

function qa_check($nome, $ok, $detalhe = '')
{
    global $qa_checks_ok, $qa_checks_falha, $qa_falhas;
    if ($ok) {
        $qa_checks_ok++;
        echo "  ok    $nome\n";
        return true;
    }
    $qa_checks_falha++;
    $qa_falhas[] = $nome . ($detalhe !== '' ? ' (' . $detalhe . ')' : '');
    echo "  FALHA $nome" . ($detalhe !== '' ? ' -> ' . $detalhe : '') . "\n";
    return false;
}

function qa_secao($titulo)
{
    echo "\n== $titulo ==\n";
}

function qa_limpar()
{
    global $qa_pdo, $qa_controle, $qa_tenants, $qa_hosts, $qa_keep, $qa_limpo;

    if ($qa_limpo) {
        return;
    }
    $qa_limpo = true;

    if ($qa_keep) {
        if (!empty($qa_hosts)) {
            echo "\n--keep: nada foi apagado. Hosts de QA:\n";
            foreach ($qa_hosts as $host) {
                echo "   https://$host/\n";
            }
            foreach ($qa_tenants as $t) {
                echo "   login: " . $t['email'] . ' / ' . $t['senha'] . "\n";
            }
        }
        return;
    }

    qa_secao('limpando o cenario de QA');

    if ($qa_pdo) {
        foreach ($qa_tenants as $t) {
            $banco = trim((string) @$t['db']);
            if ($banco !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $banco) && $banco !== $qa_controle) {
                try {
                    $qa_pdo->exec("DROP DATABASE IF EXISTS `$banco`");
                } catch (Exception $e) {
                    echo "  aviso  nao foi possivel apagar o banco $banco: " . $e->getMessage() . "\n";
                }
            }
            try {
                $qa_pdo->prepare('DELETE FROM tenant_dominios WHERE tenant_id = :id')->execute(array(':id' => (int) $t['id']));
                $qa_pdo->prepare('DELETE FROM tenants WHERE id = :id')->execute(array(':id' => (int) $t['id']));
            } catch (Exception $e) {
                echo "  aviso  nao foi possivel apagar o tenant " . $t['slug'] . ': ' . $e->getMessage() . "\n";
            }
        }
    }

    qa_sync_caddy();

    // Hosts precisam sair do ar (000 sem conexao ou 404 do Caddy)
    foreach ($qa_hosts as $host) {
        $ultimo = 0;
        for ($i = 0; $i < 6; $i++) {
            $r = qa_http('https://' . $host . '/?site=1', array('timeout' => 10));
            $ultimo = $r['status'];
            if ($ultimo === 0 || $ultimo === 404 || $ultimo === 503) {
                break;
            }
            sleep(3);
        }
        qa_check('host ' . $host . ' saiu do ar', $ultimo === 0 || $ultimo === 404 || $ultimo === 503, 'HTTP ' . $ultimo);
    }
}

register_shutdown_function('qa_limpar');

try {
    $host_bd = getenv('DB_HOST') ?: '127.0.0.1';
    $porta_bd = getenv('DB_PORT') ?: '3306';
    $qa_controle = (string) getenv('DB_NAME');

    $qa_pdo = new PDO(
        'mysql:host=' . $host_bd . ';port=' . $porta_bd . ';dbname=' . $qa_controle . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASSWORD'),
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (Exception $e) {
    fwrite(STDERR, 'Sem conexao com o banco de controle: ' . $e->getMessage() . "\n");
    exit(2);
}

/**
 * Cria (ou recria) um estabelecimento descartavel com produto e cargo.
 */
function qa_criar_tenant($slug, $nome, $segmento, $host, $tag)
{
    global $qa_pdo, $qa_controle, $qa_tenants, $qa_hosts;

    $email = $slug . '@qa.invalid';
    $senha = 'Qa#' . bin2hex(random_bytes(6));

    // Resto de execucao anterior com o mesmo slug
    $q = $qa_pdo->prepare('SELECT id, db_name FROM tenants WHERE slug = :s');
    $q->execute(array(':s' => $slug));
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $antigo) {
        $banco = trim((string) $antigo['db_name']);
        if ($banco !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $banco) && $banco !== $qa_controle) {
            $qa_pdo->exec("DROP DATABASE IF EXISTS `$banco`");
        }
        $qa_pdo->prepare('DELETE FROM tenant_dominios WHERE tenant_id = :id')->execute(array(':id' => (int) $antigo['id']));
        $qa_pdo->prepare('DELETE FROM tenants WHERE id = :id')->execute(array(':id' => (int) $antigo['id']));
    }
    $qa_pdo->prepare('DELETE FROM tenant_dominios WHERE hostname = :h')->execute(array(':h' => $host));

    $qa_pdo->prepare("INSERT INTO tenants (nome, slug, email, telefone, segmento, status, senha_crip, origem, criado_em)
                      VALUES (:nome, :slug, :email, '', :segmento, 'ativo', :hash, 'qa', NOW())")
        ->execute(array(
            ':nome'     => $nome,
            ':slug'     => $slug,
            ':email'    => $email,
            ':segmento' => $segmento,
            ':hash'     => password_hash($senha, PASSWORD_DEFAULT),
        ));
    $id = (int) $qa_pdo->lastInsertId();

    $qa_pdo->prepare("INSERT INTO tenant_dominios (hostname, tenant_id, tipo, verificado_em)
                      VALUES (:h, :id, 'subdominio', NOW())")
        ->execute(array(':h' => $host, ':id' => $id));

    $prov = bb_tenant_provisionar($qa_pdo, $id);
    if (!$prov['ok']) {
        throw new Exception('provisionamento de ' . $slug . ': ' . $prov['erro']);
    }
    bb_tenant_aplicar_segmento($qa_pdo, $id);

    $pdoT = bb_pdo_banco($prov['db']);
    $produto = 'QA Produto ' . ucfirst($segmento) . ' ' . $tag;
    $pdoT->prepare("INSERT INTO produtos (nome, descricao, categoria, valor_compra, valor_venda, estoque, foto, nivel_estoque, segmento)
                    VALUES (:nome, 'Produto descartavel do QA', 0, 0, 49.90, 4, '', 1, :segmento)")
        ->execute(array(':nome' => $produto, ':segmento' => $segmento));

    $cargo = ($segmento === 'salao') ? 'Cabeleireira' : 'Barbeiro';
    $pdoT->prepare("INSERT INTO cargos (nome) SELECT :c FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cargos WHERE nome = :c)")
        ->execute(array(':c' => $cargo));

    $qa_tenants[] = array(
        'id'     => $id,
        'slug'   => $slug,
        'db'     => $prov['db'],
        'email'  => $email,
        'senha'  => $senha,
        'base'   => 'https://' . $host,
        'host'   => $host,
        'produto' => $produto,
        'cargo'  => $cargo,
        'seg'    => $segmento,
    );
    $qa_hosts[] = $host;

    return end($qa_tenants);
}

function qa_esperar_site($base, $segundos)
{
    $limite = time() + (int) $segundos;
    $r = null;
    do {
        $r = qa_http($base . '/?site=1');
        if ($r['status'] === 200) {
            return $r;
        }
        sleep(4);
    } while (time() < $limite);
    return $r;
}

function qa_login($base, $email, $senha)
{
    $jar = tempnam(sys_get_temp_dir(), 'bbqa');
    $r = qa_http($base . '/sistema/', array('jar' => $jar));
    if ($r['status'] !== 200 || !preg_match('/name="csrf_token" value="([^"]+)"/', $r['corpo'], $m)) {
        @unlink($jar);
        return array(false, '');
    }
    $r2 = qa_http($base . '/sistema/autenticar.php', array(
        'jar'  => $jar,
        'post' => array('email' => $email, 'senha' => $senha, 'csrf_token' => $m[1]),
    ));
    $ok = ($r2['status'] === 200 && strpos($r2['corpo'], 'window.location') !== false);
    return array($ok, $jar);
}

function qa_tema_painel($t)
{
    list($logado, $jar) = qa_login($t['base'], $t['email'], $t['senha']);
    qa_check($t['seg'] . ': login no painel', $logado);
    if (!$logado) {
        return;
    }

    $login = qa_http($t['base'] . '/sistema/');
    $css_login = ($t['seg'] === 'salao') ? 'css/estilo-login-salao.css' : 'css/estilo-login.css';
    qa_check($t['seg'] . ': login usa ' . $css_login, strpos($login['corpo'], $css_login) !== false);
    if ($t['seg'] === 'salao') {
        qa_check($t['seg'] . ': login sem CSS de barbearia', strpos($login['corpo'], 'css/estilo-login.css') === false);
    } else {
        qa_check($t['seg'] . ': login sem CSS de salao', strpos($login['corpo'], 'css/estilo-login-salao.css') === false);
    }

    $painel = qa_http($t['base'] . '/sistema/painel/index.php?pag=funcionarios', array('jar' => $jar));
    qa_check($t['seg'] . ': painel responde', $painel['status'] === 200, 'HTTP ' . $painel['status']);

    $sufixo = ($t['seg'] === 'salao') ? '-salao' : '';
    qa_check($t['seg'] . ': painel usa style-novo' . $sufixo . '.css', strpos($painel['corpo'], 'css/style-novo' . $sufixo . '.css') !== false);
    qa_check($t['seg'] . ': painel usa override' . $sufixo . '.css', strpos($painel['corpo'], 'css/override' . $sufixo . '.css') !== false);

    // Select "Tema" do profissional
    $pos = strpos($painel['corpo'], 'name="segmento_user"');
    $bloco = '';
    if ($pos !== false) {
        $fim = strpos($painel['corpo'], '</select>', $pos);
        $bloco = ($fim !== false) ? substr($painel['corpo'], $pos, $fim - $pos) : substr($painel['corpo'], $pos, 600);
    }
    qa_check($t['seg'] . ': select de tema encontrado', $bloco !== '');
    $tem_ambos = strpos($bloco, 'value=""') !== false;
    $tem_proprio = strpos($bloco, 'value="' . $t['seg'] . '"') !== false;
    $outro = ($t['seg'] === 'salao') ? 'barbearia' : 'salao';
    $tem_outro = strpos($bloco, 'value="' . $outro . '"') !== false;
    qa_check($t['seg'] . ': select lista "Ambos os temas"', $tem_ambos);
    qa_check($t['seg'] . ': select lista o tema do estabelecimento', $tem_proprio);
    qa_check($t['seg'] . ': select nao lista o tema ' . $outro, !$tem_outro);

    // Cadastra um profissional com "Ambos os temas" pelo endpoint do painel
    $csrf = qa_cookie_jar($jar, 'csrf_token');
    // Nome curto de proposito: usuarios.nome e varchar(50) e o QA nao
    // precisa encostar no limite para provar o fluxo.
    $nome_prof = 'QA Prof ' . ucfirst($t['seg']) . ' ' . substr($t['produto'], -6);
    $salvar = qa_http($t['base'] . '/sistema/painel/paginas/funcionarios/salvar.php', array(
        'jar'  => $jar,
        'post' => array(
            'id'            => '',
            'nome'          => $nome_prof,
            'email'         => $t['slug'] . '.prof@qa.invalid',
            'telefone'      => '81999990000',
            'cpf'           => '',
            'cargo'         => $t['cargo'],
            'endereco'      => '',
            'atendimento'   => 'Sim',
            'tipo_chave'    => '',
            'chave_pix'     => '',
            'segmento_user' => '',
            'csrf_token'    => $csrf,
        ),
    ));
    $corpo_salvar = trim($salvar['corpo']);
        qa_check($t['seg'] . ': salvar profissional com "Ambos os temas"', $corpo_salvar === 'Salvo com Sucesso', 'HTTP ' . $salvar['status'] . ' ' . substr($corpo_salvar, 0, 140) . ($salvar['erro'] !== '' ? ' curl: ' . $salvar['erro'] : ''));

    // Nome acima do limite da coluna precisa virar aviso, nunca HTTP 500
    $longo = qa_http($t['base'] . '/sistema/painel/paginas/funcionarios/salvar.php', array(
        'jar'  => $jar,
        'post' => array(
            'id'            => '',
            'nome'          => str_repeat('A', 60),
            'email'         => $t['slug'] . '.longo@qa.invalid',
            'telefone'      => '81999990000',
            'cpf'           => '',
            'cargo'         => $t['cargo'],
            'endereco'      => '',
            'atendimento'   => 'Sim',
            'tipo_chave'    => '',
            'chave_pix'     => '',
            'segmento_user' => '',
            'csrf_token'    => $csrf,
        ),
    ));
    qa_check(
        $t['seg'] . ': nome acima de 50 caracteres avisa (sem HTTP 500)',
        $longo['status'] === 200 && qa_contem($longo['corpo'], '50 caracteres'),
        'HTTP ' . $longo['status'] . ' ' . substr(trim($longo['corpo']), 0, 100)
    );

    $agenda = qa_http($t['base'] . '/agendamentos');
    qa_check($t['seg'] . ': profissional aparece no agendamento publico', qa_contem($agenda['corpo'], $nome_prof));

    @unlink($jar);
}

try {
    qa_secao('provisionando o cenario descartavel');
    qa_sync_caddy(); // remove restos de execucoes anteriores

    $tag = date('His');
    $salao = qa_criar_tenant('zz-qa-salao', 'QA Salao Descartavel', 'salao', 'zz-qa-salao.' . $opcoes['dominio'], $tag);
    $barbearia = qa_criar_tenant('zz-qa-barbearia', 'QA Barbearia Descartavel', 'barbearia', 'zz-qa-barbearia.' . $opcoes['dominio'], $tag);
    echo "  ok    salao: id {$salao['id']} banco {$salao['db']}\n";
    echo "  ok    barbearia: id {$barbearia['id']} banco {$barbearia['db']}\n";

    qa_sync_caddy();

    qa_secao('site publico: salao');
    $home = qa_esperar_site($salao['base'], $opcoes['espera']);
    qa_check('salao: home responde 200', $home['status'] === 200, 'HTTP ' . $home['status'] . ' ' . $home['erro']);
    if ($home['status'] !== 200) {
        throw new Exception('site do salao nao respondeu; sem cenario para checar');
    }
    qa_check('salao: home usa css/style-salao.css', strpos($home['corpo'], 'css/style-salao.css') !== false);
    qa_check('salao: home sem css/style.css (barbearia)', strpos($home['corpo'], 'href="css/style.css"') === false);
    qa_check('salao: texto de produtos do salao', qa_contem($home['corpo'], 'produtos de beleza e estética com qualidade premium'));
    qa_check('salao: sem "cuidados masculinos"', !qa_contem($home['corpo'], 'cuidados masculinos'));
    qa_check('salao: produto cadastrado aparece', qa_contem($home['corpo'], $salao['produto']));
    qa_check('salao: sem faixa de troca de tema', !qa_contem($home['corpo'], 'Você tem um') && !qa_contem($home['corpo'], 'Você tem uma'));
    qa_check('salao: sem aviso de demonstracao', !qa_contem($home['corpo'], 'este site é um exemplo'));

    $produtos = qa_http($salao['base'] . '/produtos');
    qa_check('salao: produtos.php responde 200', $produtos['status'] === 200, 'HTTP ' . $produtos['status']);
    qa_check('salao: produtos.php com texto do salao', qa_contem($produtos['corpo'], 'produtos de beleza e estética com qualidade premium'));
    qa_check('salao: produtos.php sem "cuidados masculinos"', !qa_contem($produtos['corpo'], 'cuidados masculinos'));

    qa_secao('site publico: barbearia');
    $home_b = qa_esperar_site($barbearia['base'], $opcoes['espera']);
    qa_check('barbearia: home responde 200', $home_b['status'] === 200, 'HTTP ' . $home_b['status'] . ' ' . $home_b['erro']);
    if ($home_b['status'] !== 200) {
        throw new Exception('site da barbearia nao respondeu; sem cenario para checar');
    }
    qa_check('barbearia: home usa css/style.css', strpos($home_b['corpo'], 'href="css/style.css"') !== false);
    qa_check('barbearia: home sem css/style-salao.css', strpos($home_b['corpo'], 'css/style-salao.css') === false);
    qa_check('barbearia: texto masculino de produtos', qa_contem($home_b['corpo'], 'cuidados masculinos'));
    qa_check('barbearia: sem texto de produtos do salao', !qa_contem($home_b['corpo'], 'produtos de beleza e estética'));
    qa_check('barbearia: produto cadastrado aparece', qa_contem($home_b['corpo'], $barbearia['produto']));
    qa_check('barbearia: sem faixa de troca de tema', !qa_contem($home_b['corpo'], 'Você tem um') && !qa_contem($home_b['corpo'], 'Você tem uma'));

    $produtos_b = qa_http($barbearia['base'] . '/produtos');
    qa_check('barbearia: produtos.php responde 200', $produtos_b['status'] === 200, 'HTTP ' . $produtos_b['status']);
    qa_check('barbearia: produtos.php com texto masculino', qa_contem($produtos_b['corpo'], 'cuidados masculinos'));
    qa_check('barbearia: produtos.php sem texto do salao', !qa_contem($produtos_b['corpo'], 'produtos de beleza e estética'));

    qa_secao('painel e fluxo do profissional: salao');
    qa_tema_painel($salao);

    qa_secao('painel e fluxo do profissional: barbearia');
    qa_tema_painel($barbearia);
} catch (Exception $e) {
    fwrite(STDERR, "\nERRO: " . $e->getMessage() . "\n");
    qa_limpar();
    exit(2);
}

qa_limpar();

qa_secao('resumo');
$total = $qa_checks_ok + $qa_checks_falha;
echo "  $qa_checks_ok/$total checagens OK\n";
if ($qa_checks_falha > 0) {
    foreach ($qa_falhas as $falha) {
        echo "  - falhou: $falha\n";
    }
    exit(1);
}
echo "  tema dos dois segmentos consistente\n";
exit(0);
