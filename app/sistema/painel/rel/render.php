<?php
// ============================================================
// GERA O HTML DE UM RELATORIO NO PROPRIO PROCESSO
// ------------------------------------------------------------
// Os geradores de PDF (rel_*_class.php) buscavam o relatorio via HTTP no
// mesmo endereco do sistema. Num servidor com uma unica thread (php -S)
// essa requisicao para si mesmo travava a pagina ate o timeout.
// Aqui o relatorio e incluido diretamente, capturando a saida HTML.
// ============================================================

function gerar_html_relatorio($arquivo, array $parametros = array()) {
    $get_anterior = $_GET;
    $_GET = $parametros;

    // O relatorio e incluido dentro desta funcao: traz as variaveis globais
    // (nome, logo e contatos do segmento ativo) para o escopo local.
    foreach (array('pdo', 'segmento', 'nome_sistema', 'logo_rel', 'logo_sistema', 'url_sistema', 'whatsapp_sistema', 'endereco_sistema', 'email_sistema', 'telefone_fixo_sistema', 'instagram_sistema') as $var_global) {
        if (isset($GLOBALS[$var_global])) {
            ${$var_global} = $GLOBALS[$var_global];
        }
    }

    ob_start();
    include __DIR__ . '/' . $arquivo;
    $html = ob_get_clean();

    $_GET = $get_anterior;

    // Converte as URLs absolutas do site em caminhos locais, para o Dompdf
    // carregar as imagens do disco em vez de busca-las via HTTP.
    $url_site = isset($GLOBALS['url_sistema']) ? $GLOBALS['url_sistema'] : '';
    if ($url_site !== '') {
        $raiz = str_replace('\\', '/', dirname(__DIR__, 3)); // pasta app
        $html = str_replace($url_site, 'file:///' . $raiz . '/', $html);
    }

    return $html;
}
