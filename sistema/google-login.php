<?php
// Inicia o login com Google (redireciona para a tela de consentimento)
@session_start();
require_once(__DIR__ . '/conexao.php');
require_once(__DIR__ . '/google.php');

if (!bb_google_configurado()) {
    header('Location: index.php?google=indisponivel');
    exit;
}

header('Location: ' . bb_google_login_url());
exit;
