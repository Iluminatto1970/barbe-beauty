<?php
// ============================================================
// LOGIN COM GOOGLE (OAuth 2.0 / OpenID Connect)
// ------------------------------------------------------------
// Credenciais por variavel de ambiente:
//   GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET
// O redirect e sempre o do host atual (google-callback.php), o
// mesmo que guarda o cookie de sessao e o "state".
//
// So entram contas que JA existem no estabelecimento, com o
// mesmo e-mail e ativas. Nenhuma conta e criada por aqui.
// ============================================================

require_once(__DIR__ . '/tenant.php');

if (!function_exists('bb_google_config')) {
    function bb_google_config()
    {
        return array(
            'id'     => (string) getenv('GOOGLE_CLIENT_ID'),
            'secret' => (string) getenv('GOOGLE_CLIENT_SECRET'),
        );
    }
}

if (!function_exists('bb_google_configurado')) {
    function bb_google_configurado()
    {
        $cfg = bb_google_config();
        return $cfg['id'] !== '' && $cfg['secret'] !== '';
    }
}

if (!function_exists('bb_google_redirect_uri')) {
    function bb_google_redirect_uri()
    {
        global $url_sistema;
        return rtrim(isset($url_sistema) ? $url_sistema : '', '/') . '/sistema/google-callback.php';
    }
}

if (!function_exists('bb_google_login_url')) {
    function bb_google_login_url()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['bb_google_state'] = $state;

        $cfg = bb_google_config();
        $params = array(
            'client_id'     => $cfg['id'],
            'redirect_uri'  => bb_google_redirect_uri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        );
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('bb_google_http')) {
    // Requisicao HTTPS para as APIs do Google (GET ou POST de formulario)
    function bb_google_http($url, $metodo = 'GET', $dados = null, $cabecalhos = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $cabecalhos);
        $cainfo = bb_cainfo();
        if ($cainfo !== '') {
            curl_setopt($ch, CURLOPT_CAINFO, $cainfo);
        }
        if (strtoupper($metodo) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($dados) ? http_build_query($dados) : (string) $dados);
        }

        $corpo = curl_exec($ch);
        curl_close($ch);

        if ($corpo === false) {
            return null;
        }
        $json = json_decode($corpo, true);
        return is_array($json) ? $json : null;
    }
}

if (!function_exists('bb_google_trocar_codigo')) {
    function bb_google_trocar_codigo($codigo)
    {
        $cfg = bb_google_config();
        $resposta = bb_google_http(
            'https://oauth2.googleapis.com/token',
            'POST',
            array(
                'code'          => $codigo,
                'client_id'     => $cfg['id'],
                'client_secret' => $cfg['secret'],
                'redirect_uri'  => bb_google_redirect_uri(),
                'grant_type'    => 'authorization_code',
            ),
            array('Content-Type: application/x-www-form-urlencoded')
        );

        if (!is_array($resposta) || empty($resposta['access_token'])) {
            return array('ok' => false, 'erro' => 'nao foi possivel validar o acesso do Google');
        }
        return array('ok' => true, 'access_token' => (string) $resposta['access_token']);
    }
}

if (!function_exists('bb_google_usuario')) {
    function bb_google_usuario($access_token)
    {
        $dados = bb_google_http(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            'GET',
            null,
            array('Authorization: Bearer ' . $access_token)
        );

        if (!is_array($dados) || empty($dados['email'])) {
            return array('ok' => false, 'erro' => 'nao foi possivel ler o e-mail do Google');
        }

        $verificado = isset($dados['email_verified']) ? $dados['email_verified'] : false;
        if ($verificado !== true && $verificado !== 'true') {
            return array('ok' => false, 'erro' => 'o e-mail do Google nao esta verificado');
        }

        return array(
            'ok'    => true,
            'email' => strtolower(trim((string) $dados['email'])),
            'nome'  => isset($dados['name']) ? (string) $dados['name'] : '',
        );
    }
}
