<?php
// ============================================================
// SUPER ADMIN (SaaS) - autenticacao, helpers e layout
// ============================================================
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once(__DIR__ . '/../sistema/conexao.php');

// Dominio base dos subdominios dos clientes
if (!defined('SA_DOMINIO_BASE')) {
    define('SA_DOMINIO_BASE', 'iluminatto.online');
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
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', (string) $texto);
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-');
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
        KEY idx_plano (plano_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tenant_dominios (
        hostname VARCHAR(120) PRIMARY KEY,
        tenant_id INT UNSIGNED NOT NULL,
        tipo VARCHAR(12) NOT NULL DEFAULT 'subdominio',
        verificado_em DATETIME NULL,
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
}
