<?php
// ============================================================
// ENVIO DE E-MAIL TRANSACIONAL
// ------------------------------------------------------------
// A VPS nao tem sendmail e a porta 25 e bloqueada, entao o envio
// usa uma API HTTPS (Resend), configurada por variavel de ambiente:
//   RESEND_API_KEY   chave da API
//   MAIL_FROM        remetente verificado (padrao: nao-responda@dominio)
//   MAIL_FROM_NAME   nome exibido do remetente
//
// MAIL_TRANSPORT=log grava o e-mail em arquivo em vez de enviar
// (usado em testes locais).
//
// A chave pode ficar em arquivo fora do webroot (recomendado):
//   MAIL_ENV_FILE=/var/www/beauty-secrets/mail.env  (600, www-data)
// ou no padrao .secrets/mail.env da raiz do projeto.
// ============================================================

require_once(__DIR__ . '/tenant.php');

if (!function_exists('bb_email_env')) {
    // Variavel de ambiente com fallback para o arquivo de segredos
    function bb_email_env($nome, $padrao = '')
    {
        static $arquivo = null;

        $valor = getenv($nome);
        if ($valor !== false && $valor !== '') {
            return $valor;
        }

        if ($arquivo === null) {
            $arquivo = array();
            $caminho = getenv('MAIL_ENV_FILE');
            if (!$caminho) {
                // Raiz do projeto: /var/www/beauty/.secrets/mail.env
                $caminho = dirname(__DIR__) . '/.secrets/mail.env';
                if (!is_readable($caminho)) {
                    $caminho = dirname(__DIR__, 2) . '/.secrets/mail.env';
                }
            }
            if (is_readable($caminho)) {
                $linhas = @file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (is_array($linhas)) {
                    foreach ($linhas as $linha) {
                        $linha = trim($linha);
                        if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
                            continue;
                        }
                        list($chave, $conteudo) = explode('=', $linha, 2);
                        $arquivo[trim($chave)] = trim($conteudo, " \t\"'");
                    }
                }
            }
        }

        return isset($arquivo[$nome]) ? $arquivo[$nome] : $padrao;
    }
}

if (!function_exists('bb_email_marca_padrao')) {
    // Nome usado como remetente quando MAIL_FROM_NAME nao esta definido:
    // o do estabelecimento que esta enviando (barbearia, salao, o que for).
    function bb_email_marca_padrao()
    {
        $nome = isset($GLOBALS['nome_sistema']) ? trim((string) $GLOBALS['nome_sistema']) : '';
        return $nome !== '' ? $nome : 'Barbearia PRO';
    }
}

if (!function_exists('bb_email_config')) {
    function bb_email_config()
    {
        return array(
            'de'         => bb_email_env('MAIL_FROM', 'nao-responda@' . BB_DOMINIO_BASE),
            // Remetente padrao: o nome do proprio estabelecimento (um salao nao
            // recebe e-mail assinado como "Barbearia PRO").
            'nome'       => bb_email_env('MAIL_FROM_NAME', bb_email_marca_padrao()),
            'resend'     => bb_email_env('RESEND_API_KEY', ''),
            'transporte' => strtolower(bb_email_env('MAIL_TRANSPORT', '')),
        );
    }
}

if (!function_exists('bb_email_enviar')) {
    // Retorna array('ok' => bool, 'erro' => string)
    function bb_email_enviar($para, $assunto, $html, $texto = '')
    {
        $para = trim((string) $para);
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return array('ok' => false, 'erro' => 'destinatario invalido');
        }

        $cfg = bb_email_config();
        if ($texto === '') {
            $texto = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '</p>'), "\n", $html)), ENT_QUOTES, 'UTF-8'));
        }

        // Modo de teste: grava em arquivo em vez de enviar
        if ($cfg['transporte'] === 'log') {
            $arquivo = bb_email_env('MAIL_LOG_FILE', '');
            if ($arquivo === '') {
                $arquivo = sys_get_temp_dir() . '/barbearia-emails.log';
            }
            $registro = '--- ' . date('c') . " ---\nPara: $para\nAssunto: $assunto\n\n$texto\n\n";
            if (@file_put_contents($arquivo, $registro, FILE_APPEND) === false) {
                return array('ok' => false, 'erro' => 'nao foi possivel gravar o log de e-mail');
            }
            return array('ok' => true, 'erro' => '');
        }

        if ($cfg['resend'] !== '') {
            $payload = json_encode(array(
                'from'    => $cfg['nome'] . ' <' . $cfg['de'] . '>',
                'to'      => array($para),
                'subject' => $assunto,
                'html'    => $html,
                'text'    => $texto,
            ), JSON_UNESCAPED_UNICODE);

            $ch = curl_init('https://api.resend.com/emails');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $cfg['resend'],
                'Content-Type: application/json',
            ));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $cainfo = bb_cainfo();
            if ($cainfo !== '') {
                curl_setopt($ch, CURLOPT_CAINFO, $cainfo);
            }

            $corpo = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erro_curl = curl_error($ch);
            curl_close($ch);

            if ($http >= 200 && $http < 300) {
                return array('ok' => true, 'erro' => '');
            }

            $detalhe = $erro_curl !== '' ? $erro_curl : 'HTTP ' . $http;
            $json = json_decode((string) $corpo, true);
            if (is_array($json) && !empty($json['message'])) {
                $detalhe .= ': ' . $json['message'];
            }
            error_log('[barbearia-email] falha ao enviar para ' . $para . ' (' . $detalhe . ')');
            return array('ok' => false, 'erro' => 'falha no envio: ' . $detalhe);
        }

        // Ultimo recurso: mail() local (so funciona com sendmail instalado)
        $cabecalhos = "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . 'From: ' . $cfg['nome'] . ' <' . $cfg['de'] . ">\r\n";
        if (@mail($para, $assunto, $html, $cabecalhos)) {
            return array('ok' => true, 'erro' => '');
        }

        error_log('[barbearia-email] transporte nao configurado (defina RESEND_API_KEY)');
        return array('ok' => false, 'erro' => 'envio de e-mail nao configurado no servidor');
    }
}

if (!function_exists('bb_email_recuperacao_enviar')) {
    // E-mail com o link de redefinicao de senha (uso unico)
    function bb_email_recuperacao_enviar($para, $nome, $link, $marca = '')
    {
        $nome = trim((string) $nome);
        $saudacao = $nome !== '' ? 'Olá, ' . $nome . '.' : 'Olá.';
        $marca = trim((string) $marca) !== '' ? trim((string) $marca) : bb_email_marca_padrao();
        $assunto = 'Redefinir sua senha · ' . $marca;

        $html = '<div style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2430">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;padding:28px">'
            . '<p style="margin:0 0 6px;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#8a91a0">Recuperação de senha</p>'
            . '<h1 style="margin:0 0 14px;font-size:22px">Criar uma nova senha</h1>'
            . '<p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#4a5261">'
            . htmlspecialchars($saudacao, ENT_QUOTES, 'UTF-8')
            . ' Recebemos um pedido para redefinir a senha do seu acesso.</p>'
            . '<p style="margin:0 0 20px"><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" '
            . 'style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-size:15px">'
            . 'Criar nova senha</a></p>'
            . '<p style="margin:0 0 16px;font-size:13px;line-height:1.6;color:#6b7280">'
            . 'O link vale por 1 hora e só pode ser usado uma vez. Depois disso, peça outro na tela de login.</p>'
            . '<p style="margin:0;font-size:12px;line-height:1.6;color:#8a91a0">'
            . 'Se não foi você quem pediu, ignore esta mensagem: sua senha continua a mesma.</p>'
            . '</div></div>';

        $texto = $saudacao . "\n\n"
            . "Recebemos um pedido para redefinir a senha do seu acesso.\n\n"
            . $link . "\n\n"
            . "O link vale por 1 hora e só pode ser usado uma vez.\n"
            . "Se não foi você quem pediu, ignore esta mensagem.\n";

        return bb_email_enviar($para, $assunto, $html, $texto);
    }
}

if (!function_exists('bb_boas_vindas_url')) {
    function bb_boas_vindas_url($slug)
    {
        return 'https://' . $slug . '.' . BB_DOMINIO_BASE . '/sistema/';
    }
}

if (!function_exists('bb_boas_vindas_conteudo')) {
    // Assunto, HTML e texto do e-mail de boas-vindas
    function bb_boas_vindas_conteudo($tenant)
    {
        $nome = trim((string) @$tenant['nome']);
        if ($nome === '') {
            $nome = 'seu estabelecimento';
        }
        $slug = trim((string) @$tenant['slug']);
        $email = trim((string) @$tenant['email']);
        $painel = bb_boas_vindas_url($slug);
        $site = 'https://' . $slug . '.' . BB_DOMINIO_BASE . '/';

        $assunto = 'Seu acesso está liberado · ' . $nome;

        $html = '<div style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2430">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;padding:28px">'
            . '<p style="margin:0 0 6px;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#8a91a0">Acesso liberado</p>'
            . '<h1 style="margin:0 0 14px;font-size:22px">Bem-vindo(a), ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '!</h1>'
            . '<p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#4a5261">'
            . 'Seu pagamento foi confirmado e o seu sistema já está no ar. Use os dados abaixo para entrar no painel.</p>'
            . '<table role="presentation" style="width:100%;border-collapse:collapse;margin:0 0 18px">'
            . '<tr><td style="padding:10px 0;font-size:13px;color:#8a91a0;width:130px">Painel</td>'
            . '<td style="padding:10px 0;font-size:14px"><a href="' . htmlspecialchars($painel, ENT_QUOTES, 'UTF-8') . '" style="color:#2563eb">'
            . htmlspecialchars($painel, ENT_QUOTES, 'UTF-8') . '</a></td></tr>'
            . '<tr><td style="padding:10px 0;font-size:13px;color:#8a91a0">Site público</td>'
            . '<td style="padding:10px 0;font-size:14px"><a href="' . htmlspecialchars($site, ENT_QUOTES, 'UTF-8') . '" style="color:#2563eb">'
            . htmlspecialchars($site, ENT_QUOTES, 'UTF-8') . '</a></td></tr>'
            . '<tr><td style="padding:10px 0;font-size:13px;color:#8a91a0">E-mail</td>'
            . '<td style="padding:10px 0;font-size:14px">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:10px 0;font-size:13px;color:#8a91a0">Senha</td>'
            . '<td style="padding:10px 0;font-size:14px">a mesma que você cadastrou</td></tr>'
            . '</table>'
            . '<p style="margin:0 0 20px"><a href="' . htmlspecialchars($painel, ENT_QUOTES, 'UTF-8') . '" '
            . 'style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-size:15px">'
            . 'Entrar no painel</a></p>'
            . '<p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#4a5261">Primeiros passos:</p>'
            . '<ol style="margin:0 0 18px;padding-left:20px;font-size:14px;line-height:1.7;color:#4a5261">'
            . '<li>Entre no painel e edite as configurações (nome, endereço, WhatsApp e logo).</li>'
            . '<li>Cadastre serviços, profissionais e horários de atendimento.</li>'
            . '<li>Troque a senha em <strong>Editar Perfil</strong> quando quiser.</li>'
            . '</ol>'
            . '<p style="margin:0;font-size:12px;line-height:1.6;color:#8a91a0">'
            . 'Se você não reconhece este cadastro, ignore esta mensagem.</p>'
            . '</div></div>';

        $texto = "Bem-vindo(a), $nome!\n\n"
            . "Seu pagamento foi confirmado e o acesso está liberado.\n\n"
            . "Painel: $painel\n"
            . "Site público: $site\n"
            . "E-mail de acesso: $email\n"
            . "Senha: a mesma que você cadastrou\n\n"
            . "Primeiros passos: edite as configurações do estabelecimento, cadastre serviços e profissionais "
            . "e troque a senha em Editar Perfil.\n";

        return array('assunto' => $assunto, 'html' => $html, 'texto' => $texto);
    }
}

if (!function_exists('bb_boas_vindas_enviar')) {
    // Envia o e-mail de boas-vindas uma unica vez por estabelecimento.
    // $forcar = true reenvia mesmo que já tenha sido enviado.
    function bb_boas_vindas_enviar($pdo, $tenant_id, $forcar = false)
    {
        $tenant_id = (int) $tenant_id;
        if ($tenant_id <= 0) {
            return array('ok' => false, 'enviado' => false, 'erro' => 'estabelecimento invalido');
        }

        bb_tenant_garantir_colunas($pdo);

        $q = $pdo->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
        $q->execute();
        $tenant = $q->fetch(PDO::FETCH_ASSOC);
        if (!$tenant) {
            return array('ok' => false, 'enviado' => false, 'erro' => 'estabelecimento nao encontrado');
        }

        if (!$forcar && !empty($tenant['boas_vindas_em'])) {
            return array('ok' => true, 'enviado' => false, 'erro' => '');
        }

        if (trim((string) @$tenant['email']) === '') {
            return array('ok' => false, 'enviado' => false, 'erro' => 'estabelecimento sem e-mail');
        }

        $status = strtolower((string) @$tenant['status']);
        if ($status !== 'ativo' && $status !== 'trial') {
            return array('ok' => true, 'enviado' => false, 'erro' => '');
        }

        $conteudo = bb_boas_vindas_conteudo($tenant);
        $envio = bb_email_enviar($tenant['email'], $conteudo['assunto'], $conteudo['html'], $conteudo['texto']);
        if (!$envio['ok']) {
            return array('ok' => false, 'enviado' => false, 'erro' => $envio['erro']);
        }

        $pdo->prepare("UPDATE tenants SET boas_vindas_em = NOW() WHERE id = :id")
            ->execute(array(':id' => $tenant_id));

        return array('ok' => true, 'enviado' => true, 'erro' => '');
    }
}
