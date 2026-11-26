#!/usr/bin/env bash
# ============================================================
# DEPLOY REPRODUTÍVEL DO BARBEARIA PRO
# ------------------------------------------------------------
# Produção não é checkout git. Este script publica EXATAMENTE o commit
# apontado por $REF (via `git archive`) numa release datada e imutável.
#
# Layout (o docroot publicado continua sendo /var/www/beauty):
#
#   /var/www/beauty           -> /var/www/releases/current   (link criado 1x, root)
#   /var/www/releases/current -> /var/www/releases/beauty-…  (trocado a cada deploy)
#   /var/www/releases/beauty-20261125-153000-ef30853/
#
# A troca atômica acontece em `current`, que está dentro de /var/www/releases
# (do usuário deploy). O link /var/www/beauty é estável, então Caddy
# (`root * /var/www/beauty`), o gerador de sites dos tenants e qualquer
# caminho absoluto do projeto continuam válidos.
#
# Fluxo: extrai -> preserva arquivos do servidor -> troca o symlink ->
# reinicia o PHP-FPM -> smoke das rotas críticas -> se o smoke falhar, volta
# o symlink para a release anterior e valida de novo.
#
# Uso:
#   bash deploy/deploy.sh              # publica HEAD
#   REF=v1.2.3 bash deploy/deploy.sh   # publica outra ref
#   bash deploy/deploy.sh rollback     # volta para a release anterior
#
# Requisitos: `ssh vps` (usuário deploy, chave) e o bootstrap feito uma vez
# com root (deploy/bootstrap.sh).
# ============================================================
set -euo pipefail

SSH_HOST="${SSH_HOST:-vps}"
RELEASE_BASE="${RELEASE_BASE:-/var/www/releases}"
CURRENT_LINK="${CURRENT_LINK:-$RELEASE_BASE/current}"
PUBLISH_PATH="${PUBLISH_PATH:-/var/www/beauty}"
BASE_URL="${BASE_URL:-https://beauty.iluminatto.online}"
REF="${REF:-HEAD}"
KEEP="${KEEP:-5}"
SMOKE_TENTATIVAS="${SMOKE_TENTATIVAS:-20}"
# Diretórios em que o PHP (www-data) grava em tempo de execução: logos, banners,
# ícones, fotos de perfil/serviço/produto/comentário. A release sai do
# `git archive`, então nasce 644/755 do usuário deploy: sem isto o upload do
# painel passaria a falhar em silêncio (move_uploaded_file sem permissão).
WRITABLE_DIRS="${WRITABLE_DIRS:-images sistema/img sistema/painel/img}"

cd "$(dirname "$0")/.."

SMOKE_BODY="$(mktemp)"
trap 'rm -f "$SMOKE_BODY"' EXIT

log() { printf '%s %s\n' "$(date -u +%H:%M:%S)" "$*"; }
falhar() { log "ERRO: $*"; exit 1; }
remoto() { ssh -o BatchMode=yes -o ConnectTimeout=15 "$SSH_HOST" "$@"; }

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
      [ "$codigo" = "$esperado" ] || { sleep 1; continue; }
      [ -z "$agulha" ] && break
      grep -qF -- "$agulha" "$SMOKE_BODY" && break
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

# volta o symlink `current` para uma release específica
apontar_para() {
  remoto "ln -sfn '$1' '$CURRENT_LINK.novo' && mv -Tf '$CURRENT_LINK.novo' '$CURRENT_LINK'"
  remoto "sudo -n systemctl restart php8.4-fpm"
}

# ------------------------------------------------------------
# pré-checagens
# ------------------------------------------------------------
estado="$(remoto "if [ -L '$CURRENT_LINK' ]; then echo symlink; elif [ -d '$CURRENT_LINK' ]; then echo diretorio; else echo ausente; fi")"
if [ "$estado" != "symlink" ]; then
  falhar "$CURRENT_LINK está '$estado'. Rode uma vez, COMO ROOT na VPS: bash /home/deploy/bootstrap.sh"
fi

publicado="$(remoto "readlink -f '$PUBLISH_PATH'")"
case "$publicado" in
  "$RELEASE_BASE"/*) ;;
  *) falhar "$PUBLISH_PATH resolve para '$publicado', esperado algo em $RELEASE_BASE (bootstrap pendente?)" ;;
esac
remoto "test -w '$RELEASE_BASE'" || falhar "sem permissão de escrita em $RELEASE_BASE"

if [ "${1:-}" = "rollback" ]; then
  ATUAL="$(remoto "readlink -f '$CURRENT_LINK'")"
  ANTERIOR="$(remoto "ls -1dt '$RELEASE_BASE'/beauty-* 2>/dev/null | grep -vF '$ATUAL' | head -1")"
  [ -n "$ANTERIOR" ] || falhar "nenhuma release anterior em $RELEASE_BASE"
  log "rollback manual: $ATUAL -> $ANTERIOR"
  apontar_para "$ANTERIOR"
  if smoke; then
    log "rollback concluído: $(remoto "readlink -f '$CURRENT_LINK'")"
    exit 0
  fi
  falhar "smoke falhou depois do rollback manual"
fi

SHA="$(git rev-parse --short "$REF")" || falhar "ref inválida: $REF"
RELEASE="beauty-$(date -u +%Y%m%d-%H%M%S)-$SHA"
ATUAL="$(remoto "readlink -f '$CURRENT_LINK'")"

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  log "AVISO: há alterações não commitadas; será publicado só o commit $SHA."
fi
log "publicando $RELEASE (commit $SHA)"
log "release atual: $ATUAL"

# ------------------------------------------------------------
# 1. monta a release nova a partir do commit
# ------------------------------------------------------------
log "extraindo commit $SHA em $RELEASE_BASE/$RELEASE"
remoto "rm -rf '$RELEASE_BASE/.incoming' && mkdir -p '$RELEASE_BASE/.incoming'"
git archive --format=tar "$REF" | remoto "tar -xf - -C '$RELEASE_BASE/.incoming'"
remoto "chmod -R u+rwX,go+rX '$RELEASE_BASE/.incoming'"

# arquivos que existem só no servidor e não podem se perder
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
# 1.5 escrita do PHP nos diretórios de mídia
# ------------------------------------------------------------
# Mesma convenção que o bootstrap deixa na release inicial (grupo www-data
# com escrita), restrita aos diretórios que o app realmente escreve.
log "liberando escrita do PHP (www-data) em: $WRITABLE_DIRS"
remoto "cd '$RELEASE_BASE/$RELEASE' || exit 1
        for d in $WRITABLE_DIRS; do
          [ -d \"\$d\" ] || continue
          if command -v setfacl >/dev/null 2>&1; then
            setfacl -R -m u:www-data:rwX \"\$d\" &&
            find \"\$d\" -type d -exec setfacl -d -m u:www-data:rwX {} +
          else
            chgrp -R www-data \"\$d\" && chmod -R g+rwX \"\$d\"
          fi || exit 1
        done"

# ------------------------------------------------------------
# 2. troca atômica do symlink + limpeza de cache do PHP
# ------------------------------------------------------------
log "trocando $CURRENT_LINK -> $RELEASE_BASE/$RELEASE"
apontar_para "$RELEASE_BASE/$RELEASE"

# ------------------------------------------------------------
# 3. smoke + rollback automático
# ------------------------------------------------------------
log "smoke em $BASE_URL"
if smoke; then
  log "smoke OK"
else
  log "smoke FALHOU -> rollback automático para $ATUAL"
  apontar_para "$ATUAL"
  if smoke; then
    log "rollback OK: produção de volta em $ATUAL (release $RELEASE descartada)"
  else
    log "CRÍTICO: smoke falhou também após o rollback. Verifique $PUBLISH_PATH manualmente."
  fi
  exit 1
fi

# ------------------------------------------------------------
# 4. poda: mantém as KEEP releases mais recentes
# ------------------------------------------------------------
remoto "ls -1dt '$RELEASE_BASE'/beauty-* 2>/dev/null | tail -n +$((KEEP + 1)) | while read -r d; do
          [ \"\$(readlink -f \"\$d\")\" = \"\$(readlink -f '$CURRENT_LINK')\" ] && continue
          rm -rf \"\$d\" && echo '    removida: '\"\$d\"
        done"

remoto "printf '%s %s %s\n' \"\$(date -u +%Y-%m-%dT%H:%M:%SZ)\" '$RELEASE' '$SHA' >> '$RELEASE_BASE/deploy.log'"
log "deploy concluído: $(remoto "readlink -f '$PUBLISH_PATH'")"
