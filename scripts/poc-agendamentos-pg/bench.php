<?php
// PoC: mede as tres variantes da tela no MESMO conjunto de dados.
//   mysql      = codigo original, motor MySQL (baseline)
//   pg-fiel    = port para Postgres, mesma logica 1 + 3N queries
//   pg-1query  = port com join, 1 query por render
//
// Uso: php bench.php [repeticoes]

require_once __DIR__ . '/conexao-pg.php';
require_once __DIR__ . '/listar-mysql.php';
require_once __DIR__ . '/listar-pg.php';
require_once __DIR__ . '/listar-pg-otimizado.php';

$repeticoes = (int)($argv[1] ?? 20);
$funcionario = 1;
$data = date('Y-m-d');
$cartoes = 10;

$pg = poc_pg_conectar();
$my = poc_mysql_conectar();

function medir(PocPdo $pdo, callable $fn, int $n): array
{
    $fn(); // aquecimento (primeira chamada paga cache/plano)
    $html = '';
    $q0 = $pdo->queries;
    $t0 = microtime(true);
    for ($i = 0; $i < $n; $i++) {
        $html = $fn();
    }
    $ms = (microtime(true) - $t0) * 1000 / $n;

    return [
        'queries' => ($pdo->queries - $q0) / $n,
        'ms'      => $ms,
        'bytes'   => strlen($html),
        'linhas'  => substr_count($html, 'cardTarefas'),
        'md5'     => md5($html),
    ];
}

$r = [];
$r['mysql']     = medir($my, fn() => poc_listar_agendamentos_mysql($my, $funcionario, $data, $cartoes), $repeticoes);
$r['pg-fiel']   = medir($pg, fn() => poc_listar_agendamentos_pg($pg, $funcionario, $data, $cartoes), $repeticoes);
$r['pg-1query'] = medir($pg, fn() => poc_listar_agendamentos_pg_otimizado($pg, $funcionario, $data, $cartoes), $repeticoes);

printf("repeticoes por variante: %d | funcionario=%d data=%s\n\n", $repeticoes, $funcionario, $data);
printf("%-12s %8s %10s %12s %8s %6s\n", 'variante', 'queries', 'ms/render', 'ms/query', 'bytes', 'linhas');
printf("%s\n", str_repeat('-', 62));
foreach ($r as $nome => $m) {
    printf(
        "%-12s %8d %10.2f %12.3f %8d %6d\n",
        $nome,
        $m['queries'],
        $m['ms'],
        $m['queries'] > 0 ? $m['ms'] / $m['queries'] : 0,
        $m['bytes'],
        $m['linhas']
    );
}

echo "\nHTML identico entre as variantes? ";
$md5s = array_unique(array_column($r, 'md5'));
echo count($md5s) === 1 ? "SIM (md5 {$md5s[0]})\n" : "NAO (" . implode(', ', array_map(fn($k) => "$k:" . substr($r[$k]['md5'], 0, 8), array_keys($r))) . ")\n";

echo "\nresumo json: " . json_encode($r) . "\n";
