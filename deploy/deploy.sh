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
# Nas negativas usamos caminhos com extensão sensível (`.sh`): o Caddy tem
# `@sensivel` + `respond 404` para elas. `/deploy/` sozinho NÃO serve como
# teste — sem extensão ele cai no `try_files` do site e devolve a home com
# 200, o que já derrubou um deploy (rollback automático) em 2026-11-26.
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
/deploy/deploy.sh|404|
/deploy/bootstrap.sh|404|
ROTAS
  return "$falhou"
}

# volta o symlink `current` para uma release específica
apontar_para() {
  remoto "ln -sfn '$1' '$CURRENT_LINK.novo' && mv -Tf '$CURRENT_LINK.novo' '$CURRENT_LINK'"
  # O restart só limpa OPcache/realpath cache. Se o sudoers mudar ou o systemd
  # estiver ocupado, abortar aqui deixaria o site JÁ trocado e sem smoke nem
  # rollback — pior que seguir sem o restart.
  remoto "sudo -n systemctl restart php8.4-fpm" \
    || log "AVISO: nao consegui reiniciar o php8.4-fpm; seguindo para o smoke (se o site responder com arquivo antigo, rode o restart e republique)"
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

# `readlink -f` devolve um caminho mesmo quando o alvo não existe. Sem esta
# checagem o deploy seguiria com uma release atual inexistente e o rollback
# automático não teria para onde voltar.
ATUAL="$(remoto "readlink -f '$CURRENT_LINK'")"
remoto "test -d '$ATUAL'" || falhar "$CURRENT_LINK aponta para '$ATUAL', que não existe. Refaça o link (bootstrap) antes de publicar."

if [ "${1:-}" = "rollback" ]; then
  # Ordem por NOME (a data está no nome), não por mtime: a release que acabou
  # de falhar no smoke fica mais nova que a atual e, ordenando por data de
  # modificação, seria escolhida como "anterior" — publicando de novo o que
  # já tinha derrubado o smoke.
  # Só entram na conta as releases no formato datado (beauty-AAAAMMDD-HHMMSS-sha)
  # — `beauty-inicial-…` e outros nomes não ordenam cronologicamente por nome.
  # O `achou` evita que o awk devolva a última linha quando a release atual não
  # está na lista (aí não existe "anterior", e não a mais nova).
  ANTERIOR="$(remoto "ls -1d '$RELEASE_BASE'/beauty-* 2>/dev/null | grep -E '/beauty-[0-9]{8}-[0-9]{6}-' | sort | awk -v cur='$ATUAL' '{ if (\$0 == cur) { achou = 1; exit } prev = \$0 } END { if (achou) print prev }'")"
  [ -n "$ANTERIOR" ] && [ "$ANTERIOR" != "$ATUAL" ] || falhar "nenhuma release anterior a $ATUAL em $RELEASE_BASE"
  log "rollback manual: $ATUAL -> $ANTERIOR"
  apontar_para "$ANTERIOR"
  if smoke; then
    log "rollback concluído: $(remoto "readlink -f '$CURRENT_LINK'")"
    exit 0
  fi
  log "smoke falhou em $ANTERIOR -> desfazendo o rollback manual"
  apontar_para "$ATUAL"
  falhar "rollback manual desfeito: $ANTERIOR não passou no smoke; produção de volta em $ATUAL"
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

# arquivos (e diretórios) que existem só no servidor e não podem se perder
#
# A lista é lida ANTES do laço, de propósito: `ssh` consome o stdin do shell e,
# lendo direto do pipe, ele engolia as linhas seguintes do .preserve — só a
# primeira entrada era processada (silenciosamente).
PRESERVAR=()
while IFS= read -r linha; do
  linha="${linha%$'\r'}"   # .preserve editado no Windows vem com CRLF
  [ -n "$linha" ] || continue
  case "$linha" in \#*) continue ;; esac
  PRESERVAR+=("$linha")
done < <(remoto "cat '$RELEASE_BASE/.preserve' 2>/dev/null || true")

for arquivo in ${PRESERVAR[@]+"${PRESERVAR[@]}"}; do
  remoto "if [ -e '$ATUAL/$arquivo' ] && [ ! -e '$RELEASE_BASE/.incoming/$arquivo' ]; then
            mkdir -p \"\$(dirname '$RELEASE_BASE/.incoming/$arquivo')\"
            cp -a '$ATUAL/$arquivo' '$RELEASE_BASE/.incoming/$arquivo'
            echo '    preservado: $arquivo'
          elif [ ! -e '$ATUAL/$arquivo' ] && [ ! -e '$RELEASE_BASE/.incoming/$arquivo' ]; then
            echo '    AVISO: .preserve lista $arquivo, que nao existe em nenhuma das releases'
          fi"
done

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
# 1.9 sanidade antes de publicar
# ------------------------------------------------------------
# A troca do symlink é instantânea: se a árvore veio incompleta (ref errada,
# tar truncado, export-ignore novo), o site fica fora do ar até o smoke
# terminar e o rollback rodar (~1 min). Três arquivos barram isso antes.
log "conferindo a release antes de publicar"
remoto "cd '$RELEASE_BASE/$RELEASE' && for f in index.php sistema/conexao.php css/style.css; do
          [ -s \"\$f\" ] || { echo \"    faltando ou vazio: \$f\"; exit 1; }
        done" || falhar "release $RELEASE incompleta — o symlink NAO foi trocado e produção segue em $ATUAL"

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
