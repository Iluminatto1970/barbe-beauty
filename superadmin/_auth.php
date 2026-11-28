<?php
// ============================================================
// SUPER ADMIN (SaaS) - autenticacao, helpers e layout
// ============================================================
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once(__DIR__ . '/../sistema/conexao.php');

// Dominio base dos subdominios dos clientes (env do pool do php-fpm)
if (!defined('SA_DOMINIO_BASE')) {
    $sa_dominio_base = getenv('SA_DOMINIO_BASE');
    if ($sa_dominio_base === false || $sa_dominio_base === '') {
        $sa_dominio_base = getenv('BB_DOMINIO_BASE');
    }
    define('SA_DOMINIO_BASE', ($sa_dominio_base !== false && $sa_dominio_base !== '') ? trim($sa_dominio_base) : 'iluminatto.online');
}

function sa_logado()
{
    return !empty($_SESSION['superadmin_id']);
}

function sa_exigir_login()
{
    if (!sa_logado()) {
        header('Location: login.php');
        exit;
    }
}

function sa_e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function sa_moeda($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function sa_data($data)
{
    if (!$data or $data == '0000-00-00') {
        return '—';
    }
    return implode('/', array_reverse(explode('-', substr($data, 0, 10))));
}

function sa_slug($texto)
{
    // Transliteracao propria: o iconv('UTF-8','ASCII//TRANSLIT') falha no
    // Windows (devolve false) e apagava o endereco inteiro.
    $texto = function_exists('mb_strtolower') ? mb_strtolower((string) $texto, 'UTF-8') : strtolower((string) $texto);
    $texto = strtr($texto, array(
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a', 'ă' => 'a', 'ą' => 'a',
        'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ď' => 'd', 'đ' => 'd',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ě' => 'e', 'ę' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ń' => 'n', 'ñ' => 'n', 'ň' => 'n',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ő' => 'o',
        'ř' => 'r', 'š' => 's', 'ś' => 's', 'ť' => 't',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ů' => 'u',
        'ý' => 'y', 'ÿ' => 'y', 'ž' => 'z', 'ź' => 'z', 'ż' => 'z',
    ));
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim((string) $texto, '-');
}

function sa_status_badge($status)
{
    $mapa = array(
        'ativo'     => array('Ativo', 'ok'),
        'trial'     => array('Trial', 'info'),
        'pendente'  => array('Aguardando pagamento', 'warn'),
        'suspenso'  => array('Suspenso', 'warn'),
        'cancelado' => array('Cancelado', 'danger'),
    );
    $item = isset($mapa[$status]) ? $mapa[$status] : array(ucfirst($status), 'info');
    return '<span class="badge ' . $item[1] . '">' . sa_e($item[0]) . '</span>';
}

function sa_topo($titulo)
{
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . sa_e($titulo) . ' · Painel SaaS</title>';
    echo '<link rel="stylesheet" href="css/superadmin.css"></head><body>';

    if (sa_logado()) {
        $atual = basename($_SERVER['SCRIPT_NAME']);
        $menu = array(
            'index.php'         => 'Dashboard',
            'tenants.php'       => 'Estabelecimentos',
            'planos.php'        => 'Planos',
            'assinaturas.php'   => 'Assinaturas',
            'asaas.php'         => 'Cobranças',
        );
        echo '<header class="topbar"><div class="brand">⚙️ <strong>Super Admin</strong><span class="muted">painel SaaS</span></div>';
        echo '<nav class="nav">';
        foreach ($menu as $arquivo => $rotulo) {
            $ativo = ($atual == $arquivo) ? ' class="ativo"' : '';
            echo '<a href="' . $arquivo . '"' . $ativo . '>' . $rotulo . '</a>';
        }
        echo '<a class="sair" href="logout.php">Sair</a></nav></header>';
    }
    echo '<main class="wrap">';
}

function sa_rodape()
{
    echo '</main><footer class="foot">Barbearia PRO · painel do SaaS</footer></body></html>';
}

// Cria as tabelas do SaaS se ainda nao existirem
function sa_garantir_tabelas($pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS super_admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(80) NOT NULL,
        email VARCHAR(120) NOT NULL UNIQUE,
        senha_crip VARCHAR(255) NOT NULL,
        ativo VARCHAR(5) NOT NULL DEFAULT 'Sim',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS saas_planos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(60) NOT NULL,
        valor DECIMAL(10,2) NOT NULL DEFAULT 0,
        limite_profissionais INT NOT NULL DEFAULT 0,
        limite_agendamentos INT NOT NULL DEFAULT 0,
        ativo VARCHAR(5) NOT NULL DEFAULT 'Sim'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tenants (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(40) NOT NULL UNIQUE,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(120) DEFAULT NULL,
        telefone VARCHAR(20) DEFAULT NULL,
        plano_id INT UNSIGNED DEFAULT NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'trial',
        vencimento DATE DEFAULT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        db_name VARCHAR(64) DEFAULT NULL,
        site_demo TINYINT(1) NOT NULL DEFAULT 0,
        boas_vindas_em DATETIME DEFAULT NULL,
        cpf_cnpj VARCHAR(20) DEFAULT NULL,
        schema_version INT UNSIGNED NOT NULL DEFAULT 0,
        KEY idx_plano (plano_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tenant_dominios (
        hostname VARCHAR(120) PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        tipo VARCHAR(12) NOT NULL DEFAULT 'subdominio',
        token_verificacao VARCHAR(64) NULL DEFAULT NULL,
        verificado_em DATETIME NULL,
        registrado_em DATETIME NULL DEFAULT NULL,
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS saas_pagamentos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        referencia VARCHAR(20) NOT NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'pendente',
        pago_em DATETIME NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Planos padrao (idempotente): gratuito com cota e PRO mensal
    static $planos_ok = false;
    if (!$planos_ok) {
        $tem_gratis = (int) $pdo->query("SELECT COUNT(*) FROM saas_planos WHERE ativo = 'Sim' AND valor <= 0")->fetchColumn();
        if ($tem_gratis === 0) {
            $pdo->exec("INSERT INTO saas_planos (nome, valor, limite_profissionais, limite_agendamentos, ativo)
                VALUES ('Grátis', 0.00, 0, 30, 'Sim')");
        }
        $tem_pro = (int) $pdo->query("SELECT COUNT(*) FROM saas_planos WHERE ativo = 'Sim' AND valor = 49.00")->fetchColumn();
        if ($tem_pro === 0) {
            $pdo->exec("INSERT INTO saas_planos (nome, valor, limite_profissionais, limite_agendamentos, ativo)
                VALUES ('PRO', 49.00, 0, 0, 'Sim')");
        }
        $planos_ok = true;
    }
}
