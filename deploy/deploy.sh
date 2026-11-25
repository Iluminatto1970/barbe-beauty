#!/usr/bin/env bash
# ============================================================
# DEPLOY REPRODUTÍVEL DO BARBEARIA PRO
# ------------------------------------------------------------
# Produção não é checkout git. Este script publica EXATAMENTE o commit
# apontado por $REF (via `git archive`) numa release datada e imutável:
#
#   /var/www/releases/beauty-20261125-153000-ef30853/
#   /var/www/beauty -> /var/www/releases/beauty-...     (symlink)
#
# Fluxo: extrai -> confere -> troca o symlink -> reinicia o PHP-FPM ->
# smoke das rotas críticas -> se o smoke falhar, volta o symlink para a
# release anterior e valida de novo. Nada é publicado por cima de produção.
#
# Uso:
#   bash deploy/deploy.sh              # publica HEAD
#   REF=v1.2.3 bash deploy/deploy.sh   # publica outra ref
#   bash deploy/deploy.sh rollback     # volta para a release anterior
#
# Requisitos: acesso `ssh vps` (usuário deploy, chave) e o bootstrap feito
# uma vez com root (ver deploy/bootstrap.sh).
# ============================================================
set -euo pipefail

SSH_HOST="${SSH_HOST:-vps}"
RELEASE_BASE="${RELEASE_BASE:-/var/www/releases}"
APP_LINK="${APP_LINK:-/var/www/beauty}"
BASE_URL="${BASE_URL:-https://beauty.iluminatto.online}"
REF="${REF:-HEAD}"
KEEP="${KEEP:-5}"
SMOKE_TENTATIVAS="${SMOKE_TENTATIVAS:-20}"

cd "$(dirname "$0")/.."

SMOKE_BODY="$(mktemp)"
trap 'rm -f "$SMOKE_BODY"' EXIT

log() { printf '%s %s\n' "$(date -u +%H:%M:%S)" "$*"; }
falhar() { log "ERRO: $*"; exit 1; }
remoto() { ssh -o BatchMode=yes -o ConnectTimeout=15 "$SSH_HOST" "$@"; }

# ------------------------------------------------------------
# rollback manual: repointa o symlink para a release anterior
# ------------------------------------------------------------
if [ "${1:-}" = "rollback" ]; then
  ATUAL="$(remoto "readlink -f '$APP_LINK'")"
  ANTERIOR="$(remoto "ls -1dt '$RELEASE_BASE'/beauty-* | grep -v '^$ATUAL\$' | head -1")"
  [ -n "$ANTERIOR" ] || falhar "nenhuma release anterior encontrada em $RELEASE_BASE"
  log "voltando de $ATUAL para $ANTERIOR"
  remoto "ln -sfn '$ANTERIOR' '$APP_LINK.volta' && mv -Tf '$APP_LINK.volta' '$APP_LINK'"
  remoto "sudo -n systemctl restart php8.4-fpm"
  if smoke; then
    log "rollback concluído: $(remoto "readlink -f '$APP_LINK'")"
    exit 0
  fi
  falhar "smoke falhou depois do rollback manual"
fi

# ------------------------------------------------------------
# smoke das rotas críticas
# ------------------------------------------------------------
smoke() {
  local falhou=0 rota esperado agulha codigo tentativa url
  while IFS='|' read -r rota esperado agulha; do
    [ -n "$rota" ] || continue
    case "$rota" in
      *\?*) url="${BASE_URL}${rota}" ;;
      *)    url="${BASE_URL}${rota}?deploy=$(date -u +%s)" ;;
    esac
    codigo=""
    for tentativa in $(seq 1 "$SMOKE_TENTATIVAS"); do
      codigo="$(curl -sS -o "$SMOKE_BODY" -w '%{http_code}' --max-time 10 "$url" 2>/dev/null || true)"
      if [ "$codigo" = "$esperado" ] && { [ -z "$agulha" ] || grep -qF -- "$agulha" "$SMOKE_BODY"; }; then
        break
      fi
      sleep 1
    done
    if [ "$codigo" = "$esperado" ] && { [ -z "$agulha" ] || grep -qF -- "$agulha" "$SMOKE_BODY"; }; then
      log "    ok    ${rota} -> ${codigo}"
    else
      log "    FALHA ${rota} -> ${codigo:-sem resposta} (esperado ${esperado}${agulha:+, contendo \"$agulha\"})"
      falhou=1
    fi
  done <<'ROTAS'
/|200|Barbearia PRO
/landing.php|200|Barbearia
/sistema/|200|Barbearia
/css/style.css|200|
/planos.php|200|Plano
/docker-compose.yml|404|
/README.md|404|
/deploy/|404|
ROTAS
  return "$falhou"
}

# ------------------------------------------------------------
# pré-checagens
# ------------------------------------------------------------
SHA="$(git rev-parse --short "$REF")" || falhar "ref inválida: $REF"
RELEASE="beauty-$(date -u +%Y%m%d-%H%M%S)-$SHA"

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  log "AVISO: há alterações não commitadas; será publicado só o commit $SHA."
fi

estado="$(remoto "if [ -L '$APP_LINK' ]; then echo symlink; elif [ -d '$APP_LINK' ]; then echo diretorio; else echo ausente; fi")"
if [ "$estado" != "symlink" ]; then
  falhar "$APP_LINK ainda é '$estado'. Rode uma vez, COMO ROOT na VPS: bash deploy/bootstrap.sh"
fi
remoto "test -w '$RELEASE_BASE'" || falhar "sem permissão de escrita em $RELEASE_BASE (bootstrap pendente?)"

ATUAL="$(remoto "readlink -f '$APP_LINK'")"
[ -n "$ATUAL" ] || falhar "não consegui resolver o symlink $APP_LINK"
log "publicando $RELEASE (commit $SHA)"
log "release atual: $ATUAL"

# ------------------------------------------------------------
# 1. monta a release nova a partir do commit (nada de scp manual)
# ------------------------------------------------------------
log "extraindo commit $SHA para $RELEASE_BASE/.incoming"
remoto "rm -rf '$RELEASE_BASE/.incoming' && mkdir -p '$RELEASE_BASE/.incoming'"
git archive --format=tar "$REF" | remoto "tar -xf - -C '$RELEASE_BASE/.incoming'"
remoto "chmod -R u+rwX,go+rX '$RELEASE_BASE/.incoming'"

# arquivos que existem só no servidor e não podem se perder no deploy
while read -r arquivo; do
  [ -n "$arquivo" ] || continue
  remoto "if [ -f '$ATUAL/$arquivo' ] && [ ! -f '$RELEASE_BASE/.incoming/$arquivo' ]; then
            mkdir -p \"\$(dirname '$RELEASE_BASE/.incoming/$arquivo')\"
            cp -a '$ATUAL/$arquivo' '$RELEASE_BASE/.incoming/$arquivo'
            echo '    preservado: $arquivo'
          fi"
done < <(remoto "cat '$RELEASE_BASE/.preserve' 2>/dev/null || true")

remoto "mv '$RELEASE_BASE/.incoming' '$RELEASE_BASE/$RELEASE'"

# ------------------------------------------------------------
# 2. troca atômica do symlink + limpeza de cache do PHP
# ------------------------------------------------------------
log "trocando $APP_LINK -> $RELEASE_BASE/$RELEASE"
remoto "ln -sfn '$RELEASE_BASE/$RELEASE' '$APP_LINK.novo' && mv -Tf '$APP_LINK.novo' '$APP_LINK'"
remoto "sudo -n systemctl restart php8.4-fpm"

# ------------------------------------------------------------
# 3. smoke + rollback automático
# ------------------------------------------------------------
log "smoke em $BASE_URL"
if smoke; then
  log "smoke OK"
else
  log "smoke FALHOU -> rollback automático para $ATUAL"
  remoto "ln -sfn '$ATUAL' '$APP_LINK.volta' && mv -Tf '$APP_LINK.volta' '$APP_LINK'"
  remoto "sudo -n systemctl restart php8.4-fpm"
  if smoke; then
    log "rollback OK: produção de volta em $ATUAL (release $RELEASE descartada)"
  else
    log "CRÍTICO: smoke falhou também após o rollback. Verifique $APP_LINK manualmente."
  fi
  exit 1
fi

# ------------------------------------------------------------
# 4. poda: mantém as KEEP releases mais recentes
# ------------------------------------------------------------
remoto "ls -1dt '$RELEASE_BASE'/beauty-* 2>/dev/null | tail -n +$((KEEP + 1)) | while read -r d; do
          [ \"\$(readlink -f \"\$d\")\" = \"\$(readlink -f '$APP_LINK')\" ] && continue
          rm -rf \"\$d\" && echo '    removida: '\"\$d\"
        done"

remoto "printf '%s %s %s\n' \"\$(date -u +%Y-%m-%dT%H:%M:%SZ)\" '$RELEASE' '$SHA' >> '$RELEASE_BASE/deploy.log'"
log "deploy concluído: $(remoto "readlink -f '$APP_LINK'")"
