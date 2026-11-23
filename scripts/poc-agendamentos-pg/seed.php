<?php
// PoC: cria as tabelas e insere o MESMO conjunto de dados no MySQL e no
// Postgres, para a comparacao de tempo ser honesta (mesmo volume, mesmas
// linhas). Dados sinteticos: nenhum dado de cliente real.

require_once __DIR__ . '/conexao-pg.php';

$pg = poc_pg_conectar();
$my = poc_mysql_conectar();

$ddlPg = [
    'drop table if exists agendamentos, clientes, servicos, usuarios cascade',
    "create table usuarios (
        id integer primary key,
        nome varchar(50) not null,
        email varchar(50) not null,
        atendimento varchar(5) not null,
        segmento varchar(20) not null default ''
    )",
    "create table servicos (
        id integer primary key,
        nome varchar(50) not null,
        valor numeric(8,2) not null,
        ativo varchar(5) not null,
        segmento varchar(20) not null default 'barbearia'
    )",
    "create table clientes (
        id integer primary key,
        nome varchar(50) not null,
        telefone varchar(20),
        cartoes integer not null default 0,
        data_cad date not null,
        data_nasc date not null
    )",
    "create table agendamentos (
        id integer primary key,
        funcionario integer not null,
        cliente integer not null,
        data date not null,
        hora time not null,
        obs varchar(100),
        data_lanc date not null,
        usuario integer not null,
        status varchar(20) not null,
        servico integer not null,
        servicos varchar(255)
    )",
    'create index agendamentos_func_data on agendamentos (funcionario, data)',
];

$ddlMy = [
    'drop table if exists agendamentos',
    'drop table if exists clientes',
    'drop table if exists servicos',
    'drop table if exists usuarios',
    "create table usuarios (
        id int not null primary key,
        nome varchar(50) not null,
        email varchar(50) not null,
        atendimento varchar(5) not null,
        segmento varchar(20) not null default ''
    ) engine=InnoDB default charset=utf8mb4",
    "create table servicos (
        id int not null primary key,
        nome varchar(50) not null,
        valor decimal(8,2) not null,
        ativo varchar(5) not null,
        segmento varchar(20) not null default 'barbearia'
    ) engine=InnoDB default charset=utf8mb4",
    "create table clientes (
        id int not null primary key,
        nome varchar(50) not null,
        telefone varchar(20),
        cartoes int not null default 0,
        data_cad date not null,
        data_nasc date not null
    ) engine=InnoDB default charset=utf8mb4",
    "create table agendamentos (
        id int not null auto_increment primary key,
        funcionario int not null,
        cliente int not null,
        data date not null,
        hora time not null,
        obs varchar(100),
        data_lanc date not null,
        usuario int not null,
        status varchar(20) not null,
        servico int not null,
        servicos varchar(255)
    ) engine=InnoDB default charset=utf8mb4",
    'create index agendamentos_func_data on agendamentos (funcionario, data)',
];

foreach ($ddlPg as $sql) {
    $pg->exec($sql);
}
foreach ($ddlMy as $sql) {
    $my->exec($sql);
}

$usuarios = [];
for ($i = 1; $i <= 5; $i++) {
    $usuarios[] = [$i, "Profissional $i", "prof$i@exemplo.test"];
}

$servicos = [];
for ($i = 1; $i <= 12; $i++) {
    $servicos[] = [$i, "Servico $i", 25 + $i * 5];
}

$clientes = [];
for ($i = 1; $i <= 60; $i++) {
    $clientes[] = [$i, sprintf('Cliente %02d', $i), sprintf('119%08d', $i), $i % 15];
}

$agendamentos = [];
$hoje = date('Y-m-d');
$id = 0;
// 40 agendamentos do funcionario 1 no dia consultado (a tela do dia)
for ($i = 0; $i < 40; $i++) {
    $hora = sprintf('%02d:%02d:00', 8 + intdiv($i * 15, 60), ($i * 15) % 60);
    $servs = ($i % 3 === 0) ? '1' : (($i % 3 === 1) ? '1,3' : '2,5,7');
    $agendamentos[] = [
        ++$id, 1, ($i % 60) + 1, $hoje, $hora,
        ($i % 4 === 0) ? 'Cliente pediu "sem pomada"' : null,
        $hoje, ($i % 5) + 1,
        ($i % 3 === 0) ? 'Aguardando' : (($i % 3 === 1) ? 'Agendado' : 'Concluído'),
        1, $servs,
    ];
}
// mais 2 funcionarios em outros dias, para o indice nao ficar trivial
foreach ([2, 3] as $func) {
    for ($i = 0; $i < 15; $i++) {
        $hora = sprintf('%02d:%02d:00', 9 + intdiv($i * 20, 60), ($i * 20) % 60);
        $agendamentos[] = [
            ++$id, $func, (($i * 3) % 60) + 1, date('Y-m-d', strtotime('-' . $func . ' day')), $hora,
            null, $hoje, ($i % 5) + 1, 'Agendado', 2, '2',
        ];
    }
}

$inserir = function (PDO $db, bool $pg) use ($usuarios, $servicos, $clientes, $agendamentos) {
    $db->beginTransaction();

    $st = $db->prepare("insert into usuarios (id, nome, email, atendimento, segmento) values (?, ?, ?, 'Sim', '')");
    foreach ($usuarios as $u) {
        $st->execute($u);
    }

    $st = $db->prepare("insert into servicos (id, nome, valor, ativo, segmento) values (?, ?, ?, 'Sim', 'barbearia')");
    foreach ($servicos as $s) {
        $st->execute($s);
    }

    $st = $db->prepare('insert into clientes (id, nome, telefone, cartoes, data_cad, data_nasc) values (?, ?, ?, ?, ?, ?)');
    foreach ($clientes as $c) {
        $st->execute([$c[0], $c[1], $c[2], $c[3], '2025-01-10', '1990-05-20']);
    }

    if ($pg) {
        // Postgres: id vem do proprio dado (sem auto_increment)
        $st = $db->prepare('insert into agendamentos (id, funcionario, cliente, data, hora, obs, data_lanc, usuario, status, servico, servicos)
                            values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($agendamentos as $a) {
            $st->execute($a);
        }
    } else {
        // MySQL: id auto_increment, insere sem o id para provar que a
        // sequence/AUTO_INCREMENT nao muda o resultado da tela
        $st = $db->prepare('insert into agendamentos (funcionario, cliente, data, hora, obs, data_lanc, usuario, status, servico, servicos)
                            values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($agendamentos as $a) {
            $st->execute([$a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7], $a[8], $a[9], $a[10]]);
        }
    }

    $db->commit();
};

$inserir($pg, true);
$inserir($my, false);

foreach (['postgres' => $pg, 'mysql' => $my] as $nome => $db) {
    $conta = $db->query('select count(*) c from agendamentos')->fetchColumn();
    echo "$nome: agendamentos=$conta\n";
}
