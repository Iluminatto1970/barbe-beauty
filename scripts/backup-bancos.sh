#!/usr/bin/env bash
# ============================================================
# BACKUP DOS BANCOS - Barbearia PRO
# ------------------------------------------------------------
# Um banco por estabelecimento significa varios bancos. Este
# script gera um dump comprimido de TODOS eles (controle +
# clientes) no mesmo diretorio, com data/hora no nome.
#
# Alem do MariaDB, ele tambem faz o pg_dump do Postgres do
# Supabase que roda na mesma VPS (container supabase-db), que
# guarda o outro sistema (lojas/produtos/clientes + auth/storage).
#
# Uso:
#   ./scripts/backup-bancos.sh                 # em ./backups
#   ./scripts/backup-bancos.sh /var/backups/bb # em outro diretorio
#
# Variaveis (ou arquivo .secrets/db.env):
#   DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME, TENANT_DB_PREFIX
#   RETENCAO_DIAS (padrao 14; 0 desativa a limpeza)
#   PG_BACKUP (padrao 1; 0 pula o Postgres)
#   PG_CONTAINER (padrao supabase-db), PG_USER (padrao postgres)
#   PG_BANCOS (padrao "postgres"; lista separada por espaco)
#
# Restaurar um cliente (MariaDB):
#   gunzip -c <arquivo>.sql.gz | mariadb -u usuario -p
#
# Restaurar o Postgres:
#   gunzip -c pg-globais.sql.gz | psql -U postgres   # roles/ACLs primeiro
#   pg_restore -d postgres pg-postgres.dump          # depois os dados
# ============================================================
set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DESTINO="${1:-${BACKUP_DIR:-$RAIZ/backups}}"
ARQUIVO_ENV="${DB_ENV_FILE:-$RAIZ/.secrets/db.env}"

if [ -f "$ARQUIVO_ENV" ]; then
    set -a
    # shellcheck disable=SC1090
    . "$ARQUIVO_ENV"
    set +a
fi

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_NAME="${DB_NAME:-barbearia}"
PREFIXO="${TENANT_DB_PREFIX:-barbearia_t}"
RETENCAO_DIAS="${RETENCAO_DIAS:-14}"
DUMP="${MARIADB_DUMP:-mariadb-dump}"
CLIENTE="${MARIADB_CLI:-mariadb}"

PG_BACKUP="${PG_BACKUP:-1}"
PG_CONTAINER="${PG_CONTAINER:-supabase-db}"
PG_USER="${PG_USER:-postgres}"
PG_BANCOS="${PG_BANCOS:-postgres}"

export MYSQL_PWD="${DB_PASSWORD:-}"

CONEXAO=(--host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER")
CARIMBO="$(date +%Y%m%d-%H%M%S)"
PASTA="$DESTINO/$CARIMBO"
mkdir -p "$PASTA"

# Todos os bancos do sistema: o de controle + os de cada cliente
mapfile -t BANCOS < <("$CLIENTE" "${CONEXAO[@]}" -N -B -e "SHOW DATABASES" \
    | awk -v base="$DB_NAME" -v prefixo="$PREFIXO" '$1 == base || index($1, prefixo) == 1')

if [ "${#BANCOS[@]}" -eq 0 ]; then
    echo "Nenhum banco encontrado (DB_NAME=$DB_NAME, prefixo=$PREFIXO)." >&2
    exit 1
fi

for banco in "${BANCOS[@]}"; do
    if [ "$banco" = "information_schema" ] || [ "$banco" = "mysql" ] || [ "$banco" = "performance_schema" ] || [ "$banco" = "sys" ]; then
        continue
    fi
    "$DUMP" "${CONEXAO[@]}" \
        --single-transaction --quick --skip-lock-tables \
        --default-character-set=utf8mb4 --routines --triggers \
        --databases "$banco" | gzip -9 > "$PASTA/$banco.sql.gz"
    echo "ok  $banco"
done

# ============================================================
# POSTGRES DO SUPABASE (mesma VPS)
# ------------------------------------------------------------
# Sem este bloco, o banco do outro sistema ficaria sem backup
# nenhum (o agreste tem script proprio; o database "postgres",
# com lojas/produtos/clientes/auth/storage, nao tinha).
#
# Dois artefatos por execucao:
#   pg-globais.sql.gz   roles e ACLs (precisam existir antes do restore)
#   pg-<banco>.dump     dados no formato custom do pg_dump
#
# Verificacao: o TOC do arquivo e lido com pg_restore --list. Um
# dump truncado ainda teria tamanho > 0, mas falha na leitura.
# ============================================================
if [ "$PG_BACKUP" = "1" ]; then
    if ! docker inspect "$PG_CONTAINER" > /dev/null 2>&1; then
        echo "aviso: container $PG_CONTAINER nao existe; Postgres ignorado." >&2
    elif ! docker inspect --format '{{.State.Running}}' "$PG_CONTAINER" | grep -q true; then
        echo "FALHA: container $PG_CONTAINER existe mas nao esta rodando." >&2
        exit 1
    else
        # 1) Roles/ACLs: sem eles o restore volta sem GRANT e sem RLS owner.
        arquivo_globais="pg-globais.sql.gz"
        temporario_globais="$PASTA/$arquivo_globais.tmp"
        if ! docker exec "$PG_CONTAINER" pg_dumpall -U "$PG_USER" --globals-only \
            | gzip -9 > "$temporario_globais"; then
            echo "FALHA: pg_dumpall (globais) retornou erro." >&2
            rm -f "$temporario_globais"
            exit 1
        fi
        roles=0
        if [ -s "$temporario_globais" ] \
            && gzip -t "$temporario_globais" 2>/dev/null \
            && [ "$(gunzip -c "$temporario_globais" | grep -c '^CREATE ROLE')" -gt 0 ]; then
            roles=1
        fi
        if [ "$roles" -ne 1 ]; then
            echo "FALHA: arquivo de globais vazio ou ilegivel." >&2
            rm -f "$temporario_globais"
            exit 1
        fi
        mv "$temporario_globais" "$PASTA/$arquivo_globais"
        chmod 600 "$PASTA/$arquivo_globais"
        echo "ok  pg:globais"

        # 2) Um dump por database.
        for banco in $PG_BANCOS; do
            nome="pg-$banco.dump"
            temporario="$PASTA/$nome.tmp"
            if ! docker exec "$PG_CONTAINER" pg_dump -U "$PG_USER" -d "$banco" \
                --format=custom --compress=9 > "$temporario"; then
                echo "FALHA: pg_dump de $banco retornou erro." >&2
                rm -f "$temporario"
                exit 1
            fi
            if [ ! -s "$temporario" ]; then
                echo "FALHA: dump de $banco saiu vazio." >&2
                rm -f "$temporario"
                exit 1
            fi
            if ! docker exec -i "$PG_CONTAINER" pg_restore --list < "$temporario" > /dev/null 2>&1; then
                echo "FALHA: dump de $banco ilegivel (pg_restore --list falhou)." >&2
                rm -f "$temporario"
                exit 1
            fi
            mv "$temporario" "$PASTA/$nome"
            chmod 600 "$PASTA/$nome"
            echo "ok  pg:$banco"
        done
    fi
fi

# Retencao: apaga as pastas com carimbo mais antigas que RETENCAO_DIAS
# (leva junto os dumps do MariaDB e os do Postgres da mesma data).
if [ "$RETENCAO_DIAS" -gt 0 ]; then
    find "$DESTINO" -maxdepth 1 -mindepth 1 -type d -mtime +"$RETENCAO_DIAS" -exec rm -rf {} +
fi

echo "Backup concluido em $PASTA (${#BANCOS[@]} banco(s) MariaDB, retencao ${RETENCAO_DIAS}d)."
