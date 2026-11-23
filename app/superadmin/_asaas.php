<?php
// ============================================================
// INTEGRACAO ASAAS (cobranca recorrente do SaaS)
// ------------------------------------------------------------
// A chave da API NUNCA fica no codigo. Ela e lida de:
//   1) variavel de ambiente ASAAS_API_KEY (producao/VPS)
//   2) arquivo .secrets/asaas.env na raiz do projeto (local)
// Variaveis aceitas: ASAAS_API_KEY, ASAAS_WEBHOOK_TOKEN,
// ASAAS_AMBIENTE (producao|sandbox) e ASAAS_VALOR_PADRAO.
// ============================================================

require_once(__DIR__ . '/../sistema/conexao.php');

define('ASAAS_VALOR_PADRAO_PADRAO', 49.00);

if (!function_exists('asaas_env')) {
    // Le uma variavel de ambiente com fallback para .secrets/asaas.env
    function asaas_env($nome, $padrao = '')
    {
        static $arquivo = null;
        static $somente_arquivo = null;

        // Em producao o arquivo fica fora da pasta publica (ASAAS_ENV_FILE)
        if ($somente_arquivo === null) {
            $somente_arquivo = getenv('ASAAS_ENV_FILE');
        }

        if (!$somente_arquivo) {
            $valor = getenv($nome);
            if ($valor !== false && $valor !== '') {
                return $valor;
            }
        }

        if ($arquivo === null) {
            $arquivo = array();
            $caminho = $somente_arquivo ? $somente_arquivo : dirname(__DIR__, 2) . '/.secrets/asaas.env';
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

if (!function_exists('asaas_config')) {
    function asaas_config()
    {
        $ambiente = strtolower(asaas_env('ASAAS_AMBIENTE', 'producao'));
        $base = ($ambiente === 'sandbox') ? 'https://api-sandbox.asaas.com/v3' : 'https://api.asaas.com/v3';

        return array(
            'ambiente' => $ambiente,
            'base'     => $base,
            'chave'    => asaas_env('ASAAS_API_KEY', ''),
            'webhook'  => asaas_env('ASAAS_WEBHOOK_TOKEN', ''),
            'valor'    => (float) str_replace(',', '.', asaas_env('ASAAS_VALOR_PADRAO', (string) ASAAS_VALOR_PADRAO_PADRAO)),
        );
    }
}

if (!function_exists('asaas_configurado')) {
    function asaas_configurado()
    {
        $cfg = asaas_config();
        return $cfg['chave'] !== '';
    }
}

if (!function_exists('asaas_cainfo')) {
    // Bundle de CAs para validar o HTTPS do Asaas (Windows/local nao tem por padrao)
    function asaas_cainfo()
    {
        $env = asaas_env('ASAAS_CAINFO', '');
        if ($env !== '' && is_readable($env)) {
            return $env;
        }

        $ini = ini_get('openssl.cafile');
        if ($ini && is_readable($ini)) {
            return $ini;
        }

        $candidatos = array(
            dirname(__DIR__, 2) . '/.secrets/cacert.pem',
            'C:/Program Files/Git/mingw64/ssl/certs/ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
        );
        foreach ($candidatos as $caminho) {
            if (is_readable($caminho)) {
                return $caminho;
            }
        }

        return '';
    }
}

if (!function_exists('asaas_chave_mascarada')) {
    function asaas_chave_mascarada()
    {
        $cfg = asaas_config();
        if ($cfg['chave'] === '') {
            return 'não configurada';
        }
        return '••••' . substr($cfg['chave'], -4);
    }
}

if (!function_exists('asaas_req')) {
    // Retorna array('ok' => bool, 'status' => int, 'dados' => array|null, 'erro' => string)
    function asaas_req($metodo, $caminho, $dados = null)
    {
        $cfg = asaas_config();
        if ($cfg['chave'] === '') {
            return array('ok' => false, 'status' => 0, 'dados' => null, 'erro' => 'Chave da API Asaas não configurada.');
        }

        $url = rtrim($cfg['base'], '/') . '/' . ltrim($caminho, '/');
        $ch = curl_init($url);
        $cabecalhos = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'access_token: ' . $cfg['chave'],
            'User-Agent: BarbeariaPRO-SaaS',
        );

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST  => strtoupper($metodo),
            CURLOPT_HTTPHEADER     => $cabecalhos,
            CURLOPT_SSL_VERIFYPEER => true,
        ));

        $cainfo = asaas_cainfo();
        if ($cainfo !== '') {
            curl_setopt($ch, CURLOPT_CAINFO, $cainfo);
        }

        if ($dados !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados, JSON_UNESCAPED_UNICODE));
        }

        $resposta = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro_curl = curl_error($ch);
        curl_close($ch);

        if ($resposta === false) {
            return array('ok' => false, 'status' => 0, 'dados' => null, 'erro' => 'Falha de conexão: ' . $erro_curl);
        }

        $corpo = json_decode($resposta, true);
        $ok = ($status >= 200 && $status < 300);

        $erro = '';
        if (!$ok) {
            if (is_array($corpo) && isset($corpo['errors'][0]['description'])) {
                $erro = $corpo['errors'][0]['description'];
            } elseif (is_array($corpo) && isset($corpo['message'])) {
                $erro = $corpo['message'];
            } else {
                $erro = 'Erro ' . $status . ' na API do Asaas.';
            }
        }

        return array('ok' => $ok, 'status' => $status, 'dados' => $corpo, 'erro' => $erro);
    }
}

if (!function_exists('asaas_garantir_colunas')) {
    // Cria/ajusta as colunas usadas pela cobranca recorrente
    function asaas_garantir_colunas($pdo)
    {
        $mapa = array(
            'tenants' => array(
                'asaas_customer_id'     => "VARCHAR(40) NULL",
                'asaas_subscription_id' => "VARCHAR(40) NULL",
                'asaas_assinatura'      => "VARCHAR(20) NULL",
                // Dados do autocadastro do cliente
                'senha_crip'            => "VARCHAR(255) NULL",
                'token'                 => "VARCHAR(64) NULL",
                'origem'                => "VARCHAR(20) NULL",
            ),
            'saas_pagamentos' => array(
                'asaas_payment_id' => "VARCHAR(40) NULL",
                'asaas_status'     => "VARCHAR(30) NULL",
                'asaas_link'       => "VARCHAR(255) NULL",
                'asaas_venc'       => "DATE NULL",
            ),
        );

        foreach ($mapa as $tabela => $colunas) {
            try {
                $existentes = $pdo->query("SHOW COLUMNS FROM `$tabela`")->fetchAll(PDO::FETCH_COLUMN, 0);
            } catch (Exception $e) {
                continue;
            }
            if (!is_array($existentes)) {
                continue;
            }
            foreach ($colunas as $coluna => $tipo) {
                if (!in_array($coluna, $existentes, true)) {
                    try {
                        $pdo->exec("ALTER TABLE `$tabela` ADD COLUMN `$coluna` $tipo");
                    } catch (Exception $e) {
                        // coluna criada em paralelo: ignora
                    }
                }
            }
        }

        // Indice unico para o webhook nao duplicar pagamentos
        try {
            $pdo->exec("CREATE UNIQUE INDEX idx_asaas_payment ON saas_pagamentos (asaas_payment_id)");
        } catch (Exception $e) {
            // indice ja existe
        }
    }
}

if (!function_exists('asaas_cliente_do_tenant')) {
    // Garante um cliente no Asaas e devolve o id (cus_...)
    function asaas_cliente_do_tenant($pdo, $tenant)
    {
        if (!empty($tenant['asaas_customer_id'])) {
            return array('ok' => true, 'id' => $tenant['asaas_customer_id'], 'erro' => '');
        }

        $dados = array(
            'name'              => $tenant['nome'],
            'email'             => $tenant['email'],
            'externalReference' => 'tenant-' . (int) $tenant['id'],
            'notificationDisabled' => false,
        );
        if (!empty($tenant['telefone'])) {
            $dados['mobilePhone'] = preg_replace('/\D+/', '', $tenant['telefone']);
        }

        $r = asaas_req('POST', '/customers', $dados);
        if (!$r['ok']) {
            return array('ok' => false, 'id' => '', 'erro' => $r['erro']);
        }

        $id = isset($r['dados']['id']) ? $r['dados']['id'] : '';
        if ($id === '') {
            return array('ok' => false, 'id' => '', 'erro' => 'O Asaas não retornou o cliente criado.');
        }

        $q = $pdo->prepare("UPDATE tenants SET asaas_customer_id = :cid WHERE id = :id");
        $q->bindValue(':cid', $id);
        $q->bindValue(':id', (int) $tenant['id'], PDO::PARAM_INT);
        $q->execute();

        return array('ok' => true, 'id' => $id, 'erro' => '');
    }
}

if (!function_exists('asaas_criar_assinatura')) {
    // Cria a assinatura mensal e grava os ids no tenant
    function asaas_criar_assinatura($pdo, $tenant, $valor, $forma = 'UNDEFINED', $primeiro_venc = '')
    {
        $cliente = asaas_cliente_do_tenant($pdo, $tenant);
        if (!$cliente['ok']) {
            return array('ok' => false, 'erro' => $cliente['erro']);
        }

        $valor = round((float) $valor, 2);
        if ($valor <= 0) {
            return array('ok' => false, 'erro' => 'Informe um valor mensal maior que zero.');
        }

        $formas = array('UNDEFINED', 'PIX', 'BOLETO', 'CREDIT_CARD');
        if (!in_array($forma, $formas, true)) {
            $forma = 'UNDEFINED';
        }

        $venc = $primeiro_venc !== '' ? $primeiro_venc : date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
            $venc = date('Y-m-d');
        }

        $dados = array(
            'customer'          => $cliente['id'],
            'billingType'       => $forma,
            'nextDueDate'       => $venc,
            'value'             => $valor,
            'cycle'             => 'MONTHLY',
            'description'       => 'Assinatura mensal - ' . $tenant['nome'],
            'externalReference' => 'tenant-' . (int) $tenant['id'],
        );

        $r = asaas_req('POST', '/subscriptions', $dados);
        if (!$r['ok']) {
            return array('ok' => false, 'erro' => $r['erro']);
        }

        $sid = isset($r['dados']['id']) ? $r['dados']['id'] : '';
        if ($sid === '') {
            return array('ok' => false, 'erro' => 'O Asaas não retornou a assinatura criada.');
        }

        $q = $pdo->prepare("UPDATE tenants SET asaas_subscription_id = :sid, asaas_assinatura = 'ativa' WHERE id = :id");
        $q->bindValue(':sid', $sid);
        $q->bindValue(':id', (int) $tenant['id'], PDO::PARAM_INT);
        $q->execute();

        return array('ok' => true, 'erro' => '', 'id' => $sid, 'dados' => $r['dados']);
    }
}

if (!function_exists('asaas_cancelar_assinatura')) {
    function asaas_cancelar_assinatura($pdo, $tenant)
    {
        if (empty($tenant['asaas_subscription_id'])) {
            return array('ok' => false, 'erro' => 'Este estabelecimento não tem assinatura no Asaas.');
        }

        $r = asaas_req('DELETE', '/subscriptions/' . $tenant['asaas_subscription_id']);
        if (!$r['ok'] && $r['status'] !== 404) {
            return array('ok' => false, 'erro' => $r['erro']);
        }

        $q = $pdo->prepare("UPDATE tenants SET asaas_assinatura = 'cancelada' WHERE id = :id");
        $q->bindValue(':id', (int) $tenant['id'], PDO::PARAM_INT);
        $q->execute();

        return array('ok' => true, 'erro' => '');
    }
}

if (!function_exists('asaas_tenant_por_pagamento')) {
    function asaas_tenant_por_pagamento($pdo, $pagamento)
    {
        $subscription = isset($pagamento['subscription']) ? (string) $pagamento['subscription'] : '';
        $customer     = isset($pagamento['customer']) ? (string) $pagamento['customer'] : '';
        $referencia   = isset($pagamento['externalReference']) ? (string) $pagamento['externalReference'] : '';

        if ($subscription !== '') {
            $q = $pdo->prepare("SELECT * FROM tenants WHERE asaas_subscription_id = :v LIMIT 1");
            $q->execute(array(':v' => $subscription));
            $t = $q->fetch(PDO::FETCH_ASSOC);
            if ($t) {
                return $t;
            }
        }

        if ($customer !== '') {
            $q = $pdo->prepare("SELECT * FROM tenants WHERE asaas_customer_id = :v LIMIT 1");
            $q->execute(array(':v' => $customer));
            $t = $q->fetch(PDO::FETCH_ASSOC);
            if ($t) {
                return $t;
            }
        }

        if (preg_match('/^tenant-(\d+)$/', $referencia, $m)) {
            $q = $pdo->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
            $q->execute(array(':id' => (int) $m[1]));
            $t = $q->fetch(PDO::FETCH_ASSOC);
            if ($t) {
                return $t;
            }
        }

        return null;
    }
}
