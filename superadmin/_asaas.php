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
                // Documento exigido pelo Asaas para emitir a cobranca
                'cpf_cnpj'              => "VARCHAR(20) NULL",
                // Ultima conferencia da cobranca na API (conciliacao)
                'asaas_checado_em'      => "DATETIME NULL",
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

if (!function_exists('asaas_telefone_cliente')) {
    // Celular no formato que o Asaas aceita (DDD + 9 digitos, sem +55).
    // Devolve vazio quando o numero nao serve: telefone e opcional e nunca
    // pode impedir a emissao da cobranca.
    function asaas_telefone_cliente($telefone)
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefone);
        if ($digitos === '') {
            return '';
        }
        if (strlen($digitos) >= 12 && substr($digitos, 0, 2) === '55') {
            $digitos = substr($digitos, 2);
        }
        if (strlen($digitos) !== 11 || substr($digitos, 2, 1) !== '9') {
            return '';
        }
        return $digitos;
    }
}

if (!function_exists('asaas_cliente_do_tenant')) {
    // Garante um cliente no Asaas e devolve o id (cus_...)
    function asaas_cliente_do_tenant($pdo, $tenant)
    {
        $cpf_cnpj = isset($tenant['cpf_cnpj']) ? preg_replace('/\D+/', '', (string) $tenant['cpf_cnpj']) : '';

        if (!empty($tenant['asaas_customer_id'])) {
            // Cliente ja existe: o Asaas exige o documento para emitir cobranca
            if ($cpf_cnpj !== '') {
                $up = asaas_req('POST', '/customers/' . $tenant['asaas_customer_id'], array('cpfCnpj' => $cpf_cnpj));
                if (!$up['ok']) {
                    return array('ok' => false, 'id' => '', 'erro' => $up['erro']);
                }
            }
            return array('ok' => true, 'id' => $tenant['asaas_customer_id'], 'erro' => '');
        }

        $dados = array(
            'name'              => $tenant['nome'],
            'email'             => $tenant['email'],
            'externalReference' => 'tenant-' . (int) $tenant['id'],
            'notificationDisabled' => false,
        );
        $telefone = asaas_telefone_cliente(isset($tenant['telefone']) ? $tenant['telefone'] : '');
        if ($telefone !== '') {
            $dados['mobilePhone'] = $telefone;
        }
        if ($cpf_cnpj !== '') {
            $dados['cpfCnpj'] = $cpf_cnpj;
        }

        $r = asaas_req('POST', '/customers', $dados);
        if (!$r['ok'] && $telefone !== '') {
            // Numero recusado pelo Asaas nao pode derrubar o checkout:
            // cadastra o cliente sem telefone e segue para a cobranca.
            unset($dados['mobilePhone']);
            $r = asaas_req('POST', '/customers', $dados);
        }
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
        // Ja existe assinatura valida: reaproveita em vez de criar outra
        // (duas assinaturas = duas cobrancas mensais para o mesmo cliente).
        $existente = isset($tenant['asaas_subscription_id']) ? trim((string) $tenant['asaas_subscription_id']) : '';
        $situacao = isset($tenant['asaas_assinatura']) ? strtolower(trim((string) $tenant['asaas_assinatura'])) : '';
        if ($existente !== '' && $situacao !== 'cancelada') {
            return array('ok' => true, 'erro' => '', 'id' => $existente, 'reaproveitada' => true, 'dados' => null);
        }

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

if (!function_exists('asaas_evento_pago')) {
    // Eventos do Asaas que significam dinheiro recebido
    function asaas_evento_pago($evento)
    {
        return in_array((string) $evento, array('PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED_IN_CASH'), true);
    }
}

if (!function_exists('asaas_status_pago')) {
    // Situacao da cobranca na API (usada na conciliacao da tela de pagamento)
    function asaas_status_pago($status)
    {
        return in_array(strtoupper(trim((string) $status)), array('RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'), true);
    }
}

if (!function_exists('asaas_registrar_pagamento')) {
    // Grava/atualiza a cobranca em saas_pagamentos (idempotente pelo id do Asaas)
    function asaas_registrar_pagamento($pdo, $tenant_id, $pagamento, $evento)
    {
        $tenant_id  = (int) $tenant_id;
        $payment_id = isset($pagamento['id']) ? (string) $pagamento['id'] : '';
        if ($tenant_id <= 0 || $payment_id === '') {
            return array('ok' => false, 'status' => 'pendente', 'erro' => 'cobranca sem identificacao');
        }

        $valor = isset($pagamento['value']) ? (float) $pagamento['value'] : 0.0;
        $venc  = substr((string) (isset($pagamento['dueDate']) ? $pagamento['dueDate'] : date('Y-m-d')), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
            $venc = date('Y-m-d');
        }
        $link       = isset($pagamento['invoiceUrl']) ? (string) $pagamento['invoiceUrl'] : '';
        $referencia = substr($venc, 0, 7);

        $pago      = asaas_evento_pago($evento);
        $estornado = in_array((string) $evento, array('PAYMENT_REFUNDED', 'PAYMENT_DELETED', 'PAYMENT_CHARGEBACK_REQUESTED'), true);
        $status_local = $pago ? 'pago' : ($estornado ? 'estornado' : 'pendente');

        try {
            $existe = $pdo->prepare("SELECT id FROM saas_pagamentos WHERE asaas_payment_id = :pid LIMIT 1");
            $existe->execute(array(':pid' => $payment_id));
            $id_existente = $existe->fetchColumn();

            if ($id_existente) {
                $q = $pdo->prepare("UPDATE saas_pagamentos
                    SET status = :status, valor = :valor, pago_em = :pago, asaas_status = :astatus,
                        asaas_link = :link, asaas_venc = :venc
                    WHERE id = :id");
                $q->bindValue(':id', $id_existente, PDO::PARAM_INT);
            } else {
                $q = $pdo->prepare("INSERT INTO saas_pagamentos
                    (tenant_id, valor, referencia, status, pago_em, asaas_payment_id, asaas_status, asaas_link, asaas_venc, criado_em)
                    VALUES (:tenant, :valor, :ref, :status, :pago, :pid, :astatus, :link, :venc, NOW())");
                $q->bindValue(':tenant', $tenant_id, PDO::PARAM_INT);
                $q->bindValue(':ref', $referencia);
                $q->bindValue(':pid', $payment_id);
            }
            $q->bindValue(':valor', $valor);
            $q->bindValue(':status', $status_local);
            $q->bindValue(':astatus', (string) $evento);
            $q->bindValue(':link', substr($link, 0, 255));
            $q->bindValue(':venc', $venc);
            if ($pago) {
                $q->bindValue(':pago', date('Y-m-d H:i:s'));
            } else {
                $q->bindValue(':pago', null, PDO::PARAM_NULL);
            }
            $q->execute();
        } catch (Exception $e) {
            return array('ok' => false, 'status' => $status_local, 'erro' => $e->getMessage());
        }

        return array('ok' => true, 'status' => $status_local, 'erro' => '');
    }
}

if (!function_exists('asaas_ativar_acesso')) {
    // Libera o estabelecimento depois do pagamento: status ativo, vencimento
    // prorrogado (nunca retrocede), plano pago registrado, banco do cliente
    // criado e e-mail com as instrucoes de acesso. Idempotente.
    function asaas_ativar_acesso($pdo, $tenant_id, $venc_base = '')
    {
        $tenant_id = (int) $tenant_id;
        $resultado = array(
            'ok' => false, 'vencimento' => '', 'plano_id' => 0,
            'provisionado' => false, 'email' => false, 'erro' => '',
        );
        if ($tenant_id <= 0) {
            $resultado['erro'] = 'estabelecimento invalido';
            return $resultado;
        }

        $venc = (string) $venc_base;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $venc)) {
            $venc = date('Y-m-d');
        }

        // Plano pago mais barato: substitui o gratuito na liberacao
        $plano_pago = 0;
        try {
            $plano_pago = (int) $pdo->query("SELECT id FROM saas_planos WHERE ativo = 'Sim' AND valor > 0 ORDER BY valor ASC LIMIT 1")->fetchColumn();
        } catch (Exception $e) {
            $plano_pago = 0;
        }

        $trocar_plano = false;
        try {
            $q = $pdo->prepare("SELECT t.plano_id, p.valor FROM tenants t
                LEFT JOIN saas_planos p ON p.id = t.plano_id WHERE t.id = :id LIMIT 1");
            $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            $q->execute();
            $linha = $q->fetch(PDO::FETCH_ASSOC);
            if ($linha && ($linha['plano_id'] === null || (float) $linha['valor'] <= 0)) {
                $trocar_plano = true;
            }
        } catch (Exception $e) {
            $trocar_plano = false;
        }

        try {
            $sql = "UPDATE tenants SET status = 'ativo', asaas_assinatura = 'ativa',
                    vencimento = GREATEST(COALESCE(vencimento, CURDATE()), DATE_ADD(:venc, INTERVAL 1 MONTH))";
            if ($trocar_plano && $plano_pago > 0) {
                $sql .= ", plano_id = :plano";
            }
            $sql .= " WHERE id = :id";
            $q = $pdo->prepare($sql);
            $q->bindValue(':venc', $venc);
            $q->bindValue(':id', $tenant_id, PDO::PARAM_INT);
            if ($trocar_plano && $plano_pago > 0) {
                $q->bindValue(':plano', $plano_pago, PDO::PARAM_INT);
            }
            $q->execute();
        } catch (Exception $e) {
            $resultado['erro'] = 'nao foi possivel liberar: ' . $e->getMessage();
            return $resultado;
        }

        $resultado['ok'] = true;
        $resultado['plano_id'] = ($trocar_plano && $plano_pago > 0) ? $plano_pago : 0;

        try {
            $q = $pdo->prepare("SELECT vencimento FROM tenants WHERE id = :id LIMIT 1");
            $q->execute(array(':id' => $tenant_id));
            $resultado['vencimento'] = (string) $q->fetchColumn();
        } catch (Exception $e) {
            $resultado['vencimento'] = '';
        }

        // Banco do cliente (idempotente)
        try {
            if (function_exists('bb_tenant_provisionar')) {
                $provisionamento = bb_tenant_provisionar($pdo, $tenant_id);
                $resultado['provisionado'] = !empty($provisionamento['ok']);
            }
        } catch (Exception $e) {
            $resultado['provisionado'] = false;
        }

        // Instrucoes de acesso: uma vez por estabelecimento
        try {
            $arquivo_email = __DIR__ . '/../sistema/email.php';
            if (is_readable($arquivo_email)) {
                require_once($arquivo_email);
                if (function_exists('bb_boas_vindas_enviar')) {
                    $envio = bb_boas_vindas_enviar($pdo, $tenant_id);
                    $resultado['email'] = !empty($envio['enviado']);
                }
            }
        } catch (Exception $e) {
            $resultado['email'] = false;
        }

        return $resultado;
    }
}

if (!function_exists('asaas_liberar_tenant')) {
    // Passo unico usado pelo webhook e pela conciliacao: registra a cobranca e,
    // se ela esta paga, libera o acesso.
    function asaas_liberar_tenant($pdo, $tenant, $pagamento, $evento)
    {
        $tenant_id = isset($tenant['id']) ? (int) $tenant['id'] : 0;
        $registro  = asaas_registrar_pagamento($pdo, $tenant_id, $pagamento, $evento);
        $resultado = array('pago' => false, 'registro' => $registro, 'ativacao' => null);

        if (!$registro['ok'] || !asaas_evento_pago($evento)) {
            return $resultado;
        }

        $venc = isset($pagamento['dueDate']) ? substr((string) $pagamento['dueDate'], 0, 10) : date('Y-m-d');
        $resultado['ativacao'] = asaas_ativar_acesso($pdo, $tenant_id, $venc);
        $resultado['pago'] = !empty($resultado['ativacao']['ok']);

        return $resultado;
    }
}
