<?php
// PoC: conexao PDO para Postgres com contador de queries.
// O contador existe porque a tela original dispara uma query por linha
// (N+1) e o numero de round-trips e a metrica que interessa medir.

class PocPdo extends PDO
{
    public int $queries = 0;

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->queries++;
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queries++;
        return parent::prepare($query, $options);
    }

    public function exec(string $statement): int|false
    {
        $this->queries++;
        return parent::exec($statement);
    }
}

function poc_pg_conectar(): PocPdo
{
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        getenv('PG_HOST') ?: '127.0.0.1',
        getenv('PG_PORT') ?: '5432',
        getenv('PG_DB') ?: 'poc_barbearia'
    );

    return new PocPdo($dsn, getenv('PG_USER') ?: 'postgres', getenv('PG_PASS') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function poc_mysql_conectar(): PocPdo
{
    $dsn = sprintf(
        'mysql:dbname=%s;host=%s;port=%s;charset=utf8',
        getenv('MY_DB') ?: 'poc_barbearia',
        getenv('MY_HOST') ?: '127.0.0.1',
        getenv('MY_PORT') ?: '3306'
    );

    return new PocPdo($dsn, getenv('MY_USER') ?: 'root', getenv('MY_PASS') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
