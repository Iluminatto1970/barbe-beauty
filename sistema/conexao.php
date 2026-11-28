<?php 

// ============================================================
// HEADERS DE SEGURANCA (Sprint 1 - item 40)
// Enviados cedo (conexao.php e incluido no topo de todas as paginas).
// ============================================================
if (!defined('BB_HEADERS_SENT') && php_sapi_name() !== 'cli' && !headers_sent()) {
	define('BB_HEADERS_SENT', true);
	// Evita clickjacking
	header('X-Frame-Options: SAMEORIGIN');
	// Evita MIME sniffing
	header('X-Content-Type-Options: nosniff');
	// Referrer policy
	header('Referrer-Policy: strict-origin-when-cross-origin');
	// Permissoes do navegador
	header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
	// HSTS (so em HTTPS)
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
		header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	}
	// CSP basico (permite inline scripts/styles usados pelo projeto)
	header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'; frame-src https://www.google.com https://maps.google.com; frame-ancestors 'self'");
}

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
// CSRF (Sprint 1 - item 2)
// ============================================================
if (!function_exists('bb_bytes_ini')) {
    // Converte "25M"/"1G" do php.ini em bytes (0 = sem limite)
    function bb_bytes_ini($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '' || $valor === '-1') {
            return 0;
        }
        $numero = (int) $valor;
        $unidade = strtolower(substr($valor, -1));
        if ($unidade === 'g') {
            return $numero * 1073741824;
        }
        if ($unidade === 'm') {
            return $numero * 1048576;
        }
        if ($unidade === 'k') {
            return $numero * 1024;
        }
        return $numero;
    }
}

if (!function_exists('bb_csrf_token')) {
    function bb_csrf_token() {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    function bb_csrf_field() {
        $t = bb_csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
    }
    function bb_csrf_validate($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $stored = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';
        if ($stored === '' || $token === '' || $token === null) return false;
        return hash_equals($stored, (string) $token);
    }
    function bb_csrf_login_url() {
        // Tela de login do painel (mesmo host/segmento da requisicao)
        $base = isset($GLOBALS['url_sistema']) ? rtrim((string) $GLOBALS['url_sistema'], '/') : '';
        return $base . '/sistema/?sessao=expirada';
    }
    function bb_csrf_check_request() {
        // Envio maior que o post_max_size: o PHP descarta $_POST e $_FILES
        // inteiros, o token chega vazio e o usuario era mandado para o login
        // sem entender por que o formulario "nao salvou". Aqui o motivo real
        // aparece na tela (o painel mostra o texto do erro no ajax).
        $tamanho_post = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
        $limite_post = bb_bytes_ini(ini_get('post_max_size'));
        if ($tamanho_post > 0 && $limite_post > 0 && $tamanho_post > $limite_post
            && empty($_POST) && empty($_FILES)) {
            $msg_grande = 'Os arquivos enviados (' . round($tamanho_post / 1048576, 1)
                . ' MB) passam do limite de ' . max(1, (int) round($limite_post / 1048576))
                . ' MB do servidor. Envie imagens menores e tente novamente.';
            $ajax_grande = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && stripos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            if (!headers_sent()) {
                http_response_code(413);
                header('Content-Type: ' . ($ajax_grande ? 'application/json; charset=utf-8' : 'text/plain; charset=utf-8'));
                header('Cache-Control: no-store, max-age=0');
            }
            echo $ajax_grande ? json_encode(array('ok' => false, 'erro' => $msg_grande)) : $msg_grande;
            exit();
        }

        $tok = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : (isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '');
        if (bb_csrf_validate($tok)) {
            return;
        }

        // Token vencido/sessao expirada: descarta o token velho e volta para
        // o login em vez de deixar o usuario preso no texto de erro.
        unset($_SESSION['csrf_token'], $_COOKIE['csrf_token']);
        if (!headers_sent()) {
            setcookie('csrf_token', '', time() - 3600, '/', '', false, false);
        }
        $destino = bb_csrf_login_url();

        // Acao do painel via AJAX: responde 401 com o destino; o painel
        // redireciona no ajaxComplete (js do index do painel).
        $ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && stripos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($ajax) {
            if (!headers_sent()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                header('X-BB-Login: 1');
                header('Cache-Control: no-store, max-age=0');
            }
            echo json_encode(array(
                'ok'    => false,
                'erro'  => 'Sessão expirada. Entre novamente.',
                'login' => $destino,
            ));
            exit();
        }

        if (!headers_sent()) {
            header('Location: ' . $destino, true, 303);
        }
        exit();
    }
}

// Resposta simples para endpoints publicos (texto puro, sem redirect).
if (!function_exists('bb_resposta_texto')) {
    function bb_resposta_texto($status, $mensagem) {
        if (!headers_sent()) {
            http_response_code((int) $status);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store, max-age=0');
        }
        echo $mensagem;
        exit();
    }
}

// ============================================================
// INSTALACAO PRINCIPAL E DEMONSTRACAO
// ------------------------------------------------------------
// A instalacao principal (banco de controle) tem dois contextos:
//
//   site publico ....... demonstracao, em banco ficticio proprio;
//   login e painel ..... banco real do estabelecimento;
//   sessao demo ........ painel em banco ficticio (somente leitura).
//
// Assim o visitante nunca ve clientes ou agendamentos reais, e o
// proprietario continua entrando no painel de verdade. Cada
// cliente (tenant) segue com o proprio banco e agenda real.
// ============================================================
if (!function_exists('bb_instalacao_principal')) {
    function bb_instalacao_principal()
    {
        return !empty($GLOBALS['bb_instalacao_principal']);
    }
}

if (!function_exists('bb_site_demo')) {
    function bb_site_demo()
    {
        return !empty($GLOBALS['bb_site_demo']);
    }
}

// A demonstracao existe na instalacao principal e em estabelecimentos
// marcados com site_demo = 1. O painel normal continua no banco real do
// tenant; somente o site publico (e a sessao demo) usam o banco ficticio.
if (!function_exists('bb_demo_disponivel')) {
    function bb_demo_disponivel()
    {
        global $tenant_atual;
        if (!empty($GLOBALS['bb_instalacao_principal'])) {
            return true;
        }
        return ($tenant_atual && !empty($tenant_atual['site_demo']));
    }
}

// ============================================================
// MODO DEMONSTRACAO (Sprint: conta demo simula Admin, sem escrever)
// ------------------------------------------------------------
// A conta marcada com usuarios.demo = 1 entra como Administrador e
// consulta tudo, mas nao altera nada: as acoes de escrita do painel
// respondem 403. Quem e demo vem do login (sessao) e e reconferido
// no banco quando a sessao antiga nao tem a marca.
// ============================================================
if (!function_exists('bb_usuario_demo')) {
    function bb_usuario_demo()
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (isset($_SESSION['bb_demo'])) {
            return (int) $_SESSION['bb_demo'] === 1;
        }
        $id = isset($_SESSION['id']) ? (int) $_SESSION['id'] : 0;
        if ($id <= 0) {
            return false;
        }
        global $pdo;
        if (!($pdo instanceof PDO)) {
            return false;
        }
        try {
            $st = $pdo->prepare("SELECT demo FROM usuarios WHERE id = :id LIMIT 1");
            $st->execute(array(':id' => $id));
            $valor = $st->fetchColumn();
        } catch (Exception $e) {
            return false; // coluna ainda nao migrada: nao bloqueia o uso
        }
        $_SESSION['bb_demo'] = ((int) $valor === 1) ? 1 : 0;
        return (int) $valor === 1;
    }
}

if (!function_exists('bb_demo_proteger_requisicao')) {
    // Chamada por conexao.php em toda requisicao. So age no painel e
    // apenas em escrita: leituras (listagens, filtros, relatorios) passam.
    function bb_demo_proteger_requisicao()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (empty($_SESSION['id']) || !bb_usuario_demo()) {
            return;
        }

        $script = isset($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_FILENAME']) : '';
        if ($script === '') {
            return;
        }
        $dir = str_replace('\\', '/', dirname($script));
        if (strpos($dir, '/sistema/painel') === false) {
            return; // telas publicas do site seguem normais
        }

        $arquivo = basename($script);
        $metodo = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

        // Leitura: relatorios em PDF, listagens e consultas AJAX.
        if (preg_match('/^(rel_[a-z0-9_]+\.php|render\.php|listar[a-z0-9_-]*\.php|calcular\.php|quota-banner\.php)$/i', $arquivo)) {
            return;
        }

        // Escrita: nomes de acao do painel (as vezes por GET) ou qualquer POST.
        $acao = (bool) preg_match('/^(salvar|excluir|inserir|confirmar|mudar-status|baixar|alertar|limpar-permissoes|add-permiss)/i', $arquivo);
        if (!$acao && $metodo !== 'POST') {
            return;
        }

        $msg = 'Modo demonstracao: esta acao esta desabilitada nesta conta.';
        if (!headers_sent()) {
            http_response_code(403);
        }
        $ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && stripos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($ajax) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(array('ok' => false, 'erro' => $msg));
        } else {
            if (!headers_sent()) {
                header('Content-Type: text/plain; charset=utf-8');
            }
            echo $msg;
        }
        exit();
    }
}

// Helper de escape XSS (Sprint 1 - item 3)
if (!function_exists('bb_e')) {
    function bb_e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

// Escape para valor dentro de onclick="..." (string JS segura)
if (!function_exists('bb_js')) {
    function bb_js($v) {
        return htmlspecialchars(json_encode((string)$v, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    }
}

// ============================================================
// UPLOAD DE IMAGEM (Sprint 1 - item 4)
// ------------------------------------------------------------
// Valida tamanho, extensao permitida e MIME real (finfo), sem
// confiar no tipo declarado pelo navegador. Devolve '' quando o
// arquivo esta ok, ou a mensagem de erro para exibir. O
// Quem grava o arquivo e bb_imagem_salvar_upload (mais abaixo),
// que comprime/resize a imagem; o diretorio e o nome final sao
// definidos por cada tela.
// ============================================================
if (!function_exists('bb_imagem_upload_valida')) {
    // Limite alinhado ao upload_max_filesize do PHP-FPM (25M): foto de
    // celular chega com 10-20 MB e era recusada aqui com "max 10MB", mesmo
    // o servidor aceitando o envio. A imagem e reduzida logo depois.
    function bb_imagem_upload_valida($arquivo, $exts = array('png', 'jpg', 'jpeg', 'gif', 'webp'), $max_bytes = 26214400)
    {
        if (!is_array($arquivo) || empty($arquivo['name'])) {
            return '';
        }

        // Erro do proprio PHP no envio (arquivo maior que o limite do servidor,
        // envio interrompido etc). Sem isso o nome ia para o banco sem arquivo.
        $erro_php = isset($arquivo['error']) ? (int) $arquivo['error'] : UPLOAD_ERR_OK;
        if ($erro_php !== UPLOAD_ERR_OK) {
            $mapa = array(
                UPLOAD_ERR_INI_SIZE   => 'A imagem passa do limite de envio do servidor (' . (int) round($max_bytes / 1048576) . 'MB).',
                UPLOAD_ERR_FORM_SIZE  => 'A imagem passa do limite de envio permitido.',
                UPLOAD_ERR_PARTIAL    => 'O envio da imagem não terminou. Tente novamente.',
                UPLOAD_ERR_NO_TMP_DIR => 'O servidor não tem pasta temporária para o envio.',
                UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar a imagem.',
                UPLOAD_ERR_EXTENSION  => 'O envio da imagem foi bloqueado pelo servidor.',
            );
            return isset($mapa[$erro_php]) ? $mapa[$erro_php] : 'Falha no envio da imagem. Tente novamente.';
        }

        $tmp = isset($arquivo['tmp_name']) ? (string) $arquivo['tmp_name'] : '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return 'Falha no envio da imagem. Tente novamente.';
        }
        if ((int) $arquivo['size'] <= 0) {
            return 'O arquivo enviado está vazio.';
        }
        if ($max_bytes > 0 && (int) $arquivo['size'] > $max_bytes) {
            return 'Arquivo muito grande (max ' . (int) round($max_bytes / 1048576) . 'MB)!';
        }
        $ext = strtolower(pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
        $mimes = array(
            'png'  => array('image/png'),
            'jpg'  => array('image/jpeg', 'image/pjpeg'),
            'jpeg' => array('image/jpeg', 'image/pjpeg'),
            'gif'  => array('image/gif'),
            'webp' => array('image/webp'),
        );
        if (!isset($mimes[$ext]) || !in_array($ext, $exts, true)) {
            return 'Extensão de Imagem não permitida!';
        }
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string) finfo_file($finfo, $arquivo['tmp_name']) : '';
            if ($finfo) finfo_close($finfo);
        }
        if (!in_array($mime, $mimes[$ext], true)) {
            return 'Tipo de arquivo não permitido!';
        }
        return '';
    }
}

// ============================================================
// COMPRESSAO DE IMAGEM NO UPLOAD
// ------------------------------------------------------------
// Fotos de celular chegam com 10-20 MP e 5-8 MB. Guardar assim lotava
// o disco da VPS, entao todo upload passa por aqui: redimensiona o
// maior lado e recodifica com qualidade alta (sem perda visivel).
// PDF/ZIP/RAR (comprovantes) e GIF (pode ser animado) seguem intactos.
// Sem GD, ou se a compressao falhar, o arquivo e apenas movido, como
// antes: nunca perdemos o upload.
// ============================================================
if (!function_exists('bb_imagem_caminho_real')) {
    // Os scripts antigos gravam com caminho relativo ao PROPRIO arquivo
    // ("img/perfil/x.png" ou "../../img/perfil/x.png"), mas no PHP-FPM o
    // diretorio atual e o docroot. Aqui o caminho e resolvido a partir do
    // arquivo que chamou, para o upload nunca cair numa pasta inexistente.
    function bb_imagem_caminho_real($destino)
    {
        $destino = (string) $destino;
        if ($destino === '') {
            return $destino;
        }
        if ($destino[0] === '/' || $destino[0] === '\\' || preg_match('/^[A-Za-z]:[\\\/]/', $destino)) {
            return $destino;
        }
        if (is_dir(dirname($destino))) {
            return $destino;
        }

        $pilha = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
        foreach ($pilha as $quadro) {
            if (empty($quadro['file']) || $quadro['file'] === __FILE__) {
                continue;
            }
            $tentativa = dirname($quadro['file']) . '/' . $destino;
            if (is_dir(dirname($tentativa))) {
                return $tentativa;
            }
        }
        return $destino;
    }
}

if (!function_exists('bb_imagem_arquivo_mover')) {
    function bb_imagem_arquivo_mover($origem, $destino)
    {
        $destino = bb_imagem_caminho_real($destino);
        if (is_uploaded_file($origem)) {
            return move_uploaded_file($origem, $destino);
        }
        if (@rename($origem, $destino)) {
            return true;
        }
        return @copy($origem, $destino);
    }
}

if (!function_exists('bb_imagem_memoria')) {
    // Sobe o memory_limit para lidar com fotos grandes (best effort)
    function bb_imagem_memoria()
    {
        $limite = trim((string) ini_get('memory_limit'));
        if ($limite === '' || $limite === '-1') {
            return;
        }
        $multiplicador = 1;
        $unidade = strtolower(substr($limite, -1));
        if ($unidade === 'g') {
            $multiplicador = 1073741824;
        } elseif ($unidade === 'm') {
            $multiplicador = 1048576;
        } elseif ($unidade === 'k') {
            $multiplicador = 1024;
        }
        $bytes = (int) $limite * $multiplicador;
        // 24-40 MP (foto de celular) precisa de mais que 256M no GD.
        if ($bytes > 0 && $bytes < 536870912) {
            @ini_set('memory_limit', '512M');
        }
    }
}

if (!function_exists('bb_imagem_reduzir')) {
    // Recodifica a imagem no destino. Devolve false quando nao vale a pena
    // (formato nao suportado, GD ausente, arquivo maior que o original).
    function bb_imagem_reduzir($origem, $destino, $max_lado, $qualidade, $tipo)
    {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }
        $conteudo = @file_get_contents($origem);
        if ($conteudo === false) {
            return false;
        }

        bb_imagem_memoria();

        $img = @imagecreatefromstring($conteudo);
        unset($conteudo);
        if (!$img) {
            return false;
        }

        $largura = imagesx($img);
        $altura = imagesy($img);
        if ($largura < 1 || $altura < 1) {
            imagedestroy($img);
            return false;
        }

        // Reduz apenas quando passa do maior lado permitido
        $escala = 1.0;
        $max_lado = (int) $max_lado;
        if ($max_lado > 0) {
            $maior = max($largura, $altura);
            if ($maior > $max_lado) {
                $escala = $max_lado / $maior;
            }
        }

        $nova_largura = max(1, (int) round($largura * $escala));
        $nova_altura = max(1, (int) round($altura * $escala));

        $saida = $img;
        if ($nova_largura !== $largura || $nova_altura !== $altura) {
            $saida = imagecreatetruecolor($nova_largura, $nova_altura);
            if (!$saida) {
                imagedestroy($img);
                return false;
            }
            if ($tipo === IMAGETYPE_PNG || $tipo === IMAGETYPE_WEBP) {
                imagealphablending($saida, false);
                imagesavealpha($saida, true);
                imagefill($saida, 0, 0, imagecolorallocatealpha($saida, 0, 0, 0, 127));
            } else {
                imagefill($saida, 0, 0, imagecolorallocate($saida, 255, 255, 255));
            }
            imagecopyresampled($saida, $img, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura, $altura);
        }

        $temporario = $destino . '.bbtmp';
        $gravou = false;
        if ($tipo === IMAGETYPE_JPEG) {
            $gravou = @imagejpeg($saida, $temporario, (int) $qualidade);
        } elseif ($tipo === IMAGETYPE_PNG) {
            // 9 = compressao maxima do PNG (sem perda)
            $gravou = @imagepng($saida, $temporario, 9);
        } elseif ($tipo === IMAGETYPE_WEBP && function_exists('imagewebp')) {
            $gravou = @imagewebp($saida, $temporario, (int) $qualidade);
        }

        if ($saida !== $img) {
            imagedestroy($saida);
        }
        imagedestroy($img);

        if (!$gravou || !is_file($temporario)) {
            @unlink($temporario);
            return false;
        }

        // Se a recodificacao ficou maior (PNG pequeno, por exemplo), mantem o original
        if (filesize($temporario) >= filesize($origem)) {
            @unlink($temporario);
            return false;
        }

        if (!@rename($temporario, $destino)) {
            @unlink($temporario);
            return false;
        }
        @chmod($destino, 0644);
        return true;
    }
}

if (!function_exists('bb_imagem_salvar_upload')) {
    // Salva o upload no destino comprimindo imagens (JPEG/PNG/WebP).
    // $max_lado = maior dimensao final em pixels (0 = nao redimensiona).
    function bb_imagem_salvar_upload($origem, $destino, $max_lado = 1600, $qualidade = 82)
    {
        $origem = (string) $origem;
        $destino = bb_imagem_caminho_real($destino);
        if ($origem === '' || $destino === '' || !is_file($origem)) {
            return false;
        }

        $info = @getimagesize($origem);
        if (is_array($info) && !empty($info[2])) {
            $tipo = (int) $info[2];
            $pixels = (int) $info[0] * (int) $info[1];

            // Limite de seguranca contra estouro de memoria no GD
            if ($pixels > 0 && $pixels <= 40000000
                && in_array($tipo, array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP), true)) {
                if (bb_imagem_reduzir($origem, $destino, (int) $max_lado, (int) $qualidade, $tipo)) {
                    return true;
                }
            }
        }

        // PDF/ZIP/RAR, GIF (pode ser animado) e casos sem GD: grava como veio
        return bb_imagem_arquivo_mover($origem, $destino);
    }
}

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
    // Permite criar conta de teste admin@admin/123 apenas em
    // ambiente local/dev. Em producao fica sempre desativado. A env
    // SEED_TEST_USERS so pode DESLIGAR o seed: mesmo ligada, host e cliente
    // precisam ser locais (evita semear producao por variavel de ambiente).
    function bb_permitir_usuarios_teste()
    {
        $flag = getenv('SEED_TEST_USERS');
        if ($flag !== false && $flag !== '') {
            $v = strtolower(trim((string) $flag));
            if (!in_array($v, array('1', 'true', 'yes', 'on'), true)) {
                return false; // desligado explicitamente
            }
            // Ligada: segue para a checagem de host/cliente locais abaixo.
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
// WHITELIST DE TABELAS (anti SQL injection em identificadores)
// ============================================================
if (!function_exists('bb_tabela_permitida')) {
    function bb_tabela_permitida($tabela) {
        $permitidas = array('acessos','agendamentos','cargos','cat_produtos','cat_servicos','clientes','comentarios','dias','entradas','fornecedores','grupo_acessos','horarios','pagar','produtos','receber','saidas','servicos','textos_index','usuarios','usuarios_permissoes','config');
        return in_array($tabela, $permitidas, true);
    }
}

// ============================================================
// RATE LIMIT / LOCKOUT (anti brute-force)
// ------------------------------------------------------------
// Uma chave (IP ou e-mail) por arquivo em sys_get_temp_dir().
// bb_rate_limit_* devolve 0 quando liberado, ou os segundos de
// espera restantes. Login por IP: 5/15min. Por conta: 10/30min.
// ============================================================
if (!function_exists('bb_rate_limit_arquivo')) {
    function bb_rate_limit_arquivo($chave, $dir) {
        $dir = sys_get_temp_dir() . '/' . $dir;
        if (!is_dir($dir)) @mkdir($dir, 0700, true);
        return $dir . '/' . md5((string) $chave) . '.json';
    }

    function bb_rate_limit_ler($arquivo, $window) {
        $agora = time();
        $dados = array('attempts' => 0, 'first' => $agora);
        if (is_file($arquivo)) {
            $lido = json_decode((string) @file_get_contents($arquivo), true);
            if (is_array($lido)) $dados = $lido;
        }
        if ($agora - (int) $dados['first'] > $window) {
            $dados = array('attempts' => 0, 'first' => $agora);
        }
        return $dados;
    }

    function bb_rate_limit_espera($chave, $dir, $max, $window) {
        $dados = bb_rate_limit_ler(bb_rate_limit_arquivo($chave, $dir), $window);
        if ((int) $dados['attempts'] >= $max) {
            return (int) ($dados['first'] + $window - time());
        }
        return 0;
    }

    function bb_rate_limit_falha($chave, $dir, $window) {
        $arquivo = bb_rate_limit_arquivo($chave, $dir);
        $dados = bb_rate_limit_ler($arquivo, $window);
        $dados['attempts'] = (int) $dados['attempts'] + 1;
        @file_put_contents($arquivo, json_encode($dados), LOCK_EX);
    }

    function bb_rate_limit_limpar($chave, $dir) {
        @unlink(bb_rate_limit_arquivo($chave, $dir));
    }

    function bb_rate_limit_ip() {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    function bb_rate_limit_login($max = 5, $window = 900) {
        return bb_rate_limit_espera(bb_rate_limit_ip(), 'bb_login', $max, $window);
    }
    function bb_rate_limit_login_fail($window = 900) {
        bb_rate_limit_falha(bb_rate_limit_ip(), 'bb_login', $window);
    }
    function bb_rate_limit_login_ok() {
        bb_rate_limit_limpar(bb_rate_limit_ip(), 'bb_login');
    }

    function bb_rate_limit_conta($email, $max = 10, $window = 1800) {
        $email = strtolower(trim((string) $email));
        return $email === '' ? 0 : bb_rate_limit_espera($email, 'bb_conta', $max, $window);
    }
    function bb_rate_limit_conta_fail($email, $window = 1800) {
        $email = strtolower(trim((string) $email));
        if ($email !== '') bb_rate_limit_falha($email, 'bb_conta', $window);
    }
    function bb_rate_limit_conta_ok($email) {
        $email = strtolower(trim((string) $email));
        if ($email !== '') bb_rate_limit_limpar($email, 'bb_conta');
    }

    // Limite para formularios publicos (cadastro, agendamento, contato).
    function bb_rate_limit_publico_espera($escopo, $max, $window) {
        $escopo = preg_replace('/[^a-z0-9_]/i', '', (string) $escopo);
        return bb_rate_limit_espera(bb_rate_limit_ip(), 'bb_pub_' . $escopo, $max, $window);
    }
    function bb_rate_limit_publico_conta($escopo, $window) {
        $escopo = preg_replace('/[^a-z0-9_]/i', '', (string) $escopo);
        bb_rate_limit_falha(bb_rate_limit_ip(), 'bb_pub_' . $escopo, $window);
    }
}
// ============================================================
//  MULTI-TENANT
// ------------------------------------------------------------
// O banco de controle guarda os estabelecimentos e assinaturas.
// Quando o dominio acessado pertence a um cliente, a conexao e
// trocada para o banco de dados daquele cliente.
// ============================================================
require_once(__DIR__ . '/tenant.php');
require_once(__DIR__ . '/demo-banco.php');

$bb_banco_controle = $banco;
$bb_tenant_db_atual = $banco;
$bb_instalacao_principal = true;
$bb_site_demo = false;
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
    if ($bb_tenant_host && bb_tenant_banco($bb_tenant_host) !== $banco && empty($bb_tenant_host['site_demo'])) {
        http_response_code(404);
        echo 'Nao encontrado.';
        exit;
    }
} else {
    $tenant_atual = bb_tenant_resolver($pdo_controle, $bb_host_req);
    if ($tenant_atual) {
        $tenant_id_atual = (int) $tenant_atual['id'];
        $bb_tenant_db_atual = bb_tenant_banco($tenant_atual);
    } elseif (!bb_host_controle($bb_host_req)) {
        // Host sem estabelecimento cadastrado: nao cai no banco de controle,
        // para nunca exibir os dados de outro estabelecimento.
        http_response_code(404);
        echo 'Estabelecimento nao encontrado.';
        exit;
    }
}

// No dominio do cliente o banco e o dele (nunca demo). Na instalacao
// principal, so o site publico e a sessao demo do painel usam o banco
// ficticio; o login e o painel normal continuam no banco real.
$bb_instalacao_principal = ($bb_tenant_db_atual === $banco);
if (bb_demo_disponivel() && !bb_requisicao_de_controle($bb_caminho_req)) {
    // Funciona com o sistema na raiz ou em subpasta (XAMPP, /barbearia).
    $bb_em_sistema = (bool) preg_match('~(^|/)sistema(/|$)~', $bb_caminho_req);
    $bb_em_painel = (bool) preg_match('~(^|/)sistema/painel(/|$)~', $bb_caminho_req);
    $bb_sessao_demo = false;
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (!empty($_SESSION['bb_demo'])) {
        $bb_sessao_demo = true;
    }

    // Site publico: sempre demo. Painel: demo apenas na sessao iniciada
    // pelo botao "Entrar na demonstracao" (bb_demo). Login, recuperacao
    // de senha e painel normal seguem no banco real.
    if ((!$bb_em_sistema && !$bb_em_painel) || ($bb_em_painel && $bb_sessao_demo)) {
        $bb_site_demo = true;
        $bb_tenant_db_atual = bb_demo_banco_nome();
    }
}

// Estabelecimento suspenso ou cancelado: site e painel fora do ar
if (bb_tenant_site_indisponivel()) {
    bb_tenant_responder_indisponivel();
}

if ($bb_tenant_db_atual !== $banco) {
    // O painel do cliente fala com o banco dele pela credencial DELE
    // (bb_t<id>, GRANT so no proprio schema): SQL injetado no site de um
    // estabelecimento nao alcanca o banco dos outros. A credencial
    // compartilhada acima so serve para o banco de controle.
    $bb_cred_tenant = null;
    if (!$bb_site_demo) {
        $bb_cred_tenant = bb_tenant_db_credenciais($tenant_atual);
        if (!$bb_cred_tenant) {
            // Tenant provisionado antes deste recurso: cria a credencial agora.
            $bb_cred_tenant = bb_tenant_db_preparar($pdo_controle, $tenant_atual);
        }
    }

    $bb_erro_bd = null;
    try {
        if ($bb_site_demo) {
            $pdo = bb_demo_garantir_banco($pdo_controle);
        } elseif ($bb_cred_tenant) {
            $pdo = bb_pdo_tenant($bb_cred_tenant);
        } else {
            $pdo = new PDO("mysql:dbname=" . $bb_tenant_db_atual . ";host=$servidor;port=$porta;charset=utf8", "$usuario", "$senha");
        }
    } catch (Exception $e) {
        $bb_erro_bd = $e;
        $pdo = null;

        // Credencial dedicada ausente/desatualizada nao pode derrubar o site
        // do cliente: tenta a compartilhada e deixa o registro para o operador
        // (nenhuma senha vai para o log).
        if ($bb_cred_tenant) {
            error_log('tenant-db: credencial do estabelecimento ' . (int) $tenant_id_atual
                . ' falhou (' . $e->getMessage() . '); usando a credencial compartilhada ate o proximo provisionamento.');
            try {
                $pdo = new PDO("mysql:dbname=" . $bb_tenant_db_atual . ";host=$servidor;port=$porta;charset=utf8", "$usuario", "$senha");
                $bb_erro_bd = null;
            } catch (Exception $e2) {
                $bb_erro_bd = $e2;
                $pdo = null;
            }
        }
    }

    if (!$pdo) {
        if ($bb_site_demo) {
            // Sem banco ficticio nao existe demonstracao: nunca cai no
            // banco de controle, que tem os dados reais.
            if (!headers_sent()) {
                http_response_code(503);
                header('Content-Type: text/plain; charset=utf-8');
                header('Cache-Control: no-store, max-age=0');
            }
            echo 'A demonstração está temporariamente indisponível.';
            exit;
        }
        if (getenv('SHOW_DB_ERROR') === '1') {
            echo 'Nao conectado ao banco de dados do estabelecimento! <br><br>' . ($bb_erro_bd ? $bb_erro_bd->getMessage() : '');
        } else {
            echo 'Este estabelecimento ainda nao esta configurado. Fale com o suporte.';
        }
        exit;
    }
}

if (!function_exists('bb_tel_whatsapp_numero')) {
    // Numero no formato do wa.me: so digitos e com o 55 na frente (uma vez).
    // Configuracao com "+55 (81) 98660-7510" antes virava 555581...
    function bb_tel_whatsapp_numero($numero)
    {
        $digitos = preg_replace('/\D+/', '', (string) $numero);
        if ($digitos === '') {
            return '';
        }
        if (strlen($digitos) >= 12 && substr($digitos, 0, 2) === '55') {
            return $digitos;
        }
        return '55' . $digitos;
    }
}

// VARIAVEIS DO SISTEMA (fallback usado somente na primeira instalacao)
$nome_sistema      = 'Barbearia PRO';
$email_sistema     = 'admin@admin';
$whatsapp_sistema  = '(11) 9999-9999';
$segmento          = 'barbearia';
$tipo_rel          = 'pdf';
$telefone_fixo_sistema = '';
$endereco_sistema  = '';
$logo_rel          = 'logo_rel.jpg';
$logo_sistema      = 'logo.png';
$icone_sistema     = 'favicon.ico';
$instagram_sistema = '';
$tipo_comissao     = 'Porcentagem';
$texto_rodape      = 'Edite este texto nas configuracoes do painel administrativo';
$img_banner_index  = 'banner.jpg';
$icone_site        = 'favicon.png';
$texto_sobre       = '';
$imagem_sobre      = 'foto_barbearia.jpg';
$mapa              = '';
$quantidade_cartoes = 10;
$texto_fidelidade  = '';
$texto_agendamento = 'Selecionar Prestador de Servico';
$msg_agendamento   = 'Sim';
$tel_whatsapp      = bb_tel_whatsapp_numero($whatsapp_sistema);

// O banco de controle nao tem as tabelas de um estabelecimento. Quando
// a requisicao e do SaaS (planos/assinatura), segue com os defaults acima.
try {
    $query = $pdo->query("SELECT * from config ");
} catch (Exception $e) {
    $query = false;
}
if ($query) {
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total_reg = @count($res);
    if ($total_reg == 0) {
        // Primeira instalacao: cria a config padrao e rele para definir as variaveis
        $pdo->prepare("INSERT INTO config SET nome = :nome, email = :email, telefone_whatsapp = :whatsapp, logo = 'logo.png', icone = 'favicon.ico', logo_rel = 'logo_rel.jpg', tipo_rel = 'pdf', tipo_comissao = 'Porcentagem', texto_rodape = 'Edite este texto nas configuracoes do painel administrador', img_banner_index = 'banner.jpg', quantidade_cartoes = 10, texto_agendamento = 'Selecionar Prestador de Servico', msg_agendamento = 'Sim'")
            ->execute(array(':nome' => $nome_sistema, ':email' => $email_sistema, ':whatsapp' => $whatsapp_sistema));
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

        $tel_whatsapp = bb_tel_whatsapp_numero($whatsapp_sistema);
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
            $tel_whatsapp       = bb_tel_whatsapp_numero($whatsapp_sistema);
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
            $tel_whatsapp     = bb_tel_whatsapp_numero($whatsapp_sistema);
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

// Plano e cota de agendamentos do SaaS (usa o tenant resolvido acima)
require_once(__DIR__ . '/planos.php');

// Conta demo: simula Administrador, nunca escreve
bb_demo_proteger_requisicao();

// Cookie CSRF (nao-HttpOnly para leitura via JS/AJAX) - apos definicao dos helpers
if (php_sapi_name() !== 'cli' && !headers_sent()) {
	$ct = bb_csrf_token();
	setcookie('csrf_token', $ct, 0, '/', '', false, false);
	$_COOKIE['csrf_token'] = $ct;
}

?>
