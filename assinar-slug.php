<?php
// ============================================================
// VERIFICACAO DO ENDERECO (SLUG) EM TEMPO REAL
// Responde em JSON se o endereco digitado no autocadastro esta
// livre, reservado ou ja em uso. Usa exatamente a mesma regra
// do envio do formulario (bb_slug_avaliar / bb_slug_motivo),
// para o cliente nao descobrir o problema so depois de enviar.
// ============================================================
require_once(__DIR__ . '/superadmin/_auth.php');
sa_garantir_tabelas($pdo);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$slug = sa_slug(isset($_GET['slug']) ? $_GET['slug'] : '');
$avaliacao = bb_slug_avaliar($pdo, $slug, 0);

echo json_encode(array(
    'slug'   => $avaliacao['slug'],
    'host'   => $avaliacao['host'],
    'estado' => $avaliacao['estado'],
    'motivo' => $avaliacao['motivo'],
    'livre'  => $avaliacao['estado'] === 'livre',
), JSON_UNESCAPED_UNICODE);
