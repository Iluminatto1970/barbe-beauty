#!/usr/bin/env bash
# ============================================================
# BOOTSTRAP DO LAYOUT DE RELEASES (rodar UMA vez, COMO ROOT na VPS)
# ------------------------------------------------------------
# Converte /var/www/beauty (hoje um diretório real) em symlink para uma
# release datada em /var/www/releases, que é onde deploy/deploy.sh publica.
#
#   /var/www/beauty -> /var/www/releases/beauty-inicial-<data>
#
# O docroot publicado continua sendo /var/www/beauty: por isso o Caddyfile,
# o gerador de sites dos tenants (/usr/local/sbin/beauty-sync-tenants.php,
# que usa `root * /var/www/beauty`) e qualquer caminho absoluto do projeto
# continuam válidos sem alteração.
#
# Uso (na VPS, como root):  bash deploy/bootstrap.sh
# ============================================================
set -euo pipefail

APP_LINK="${APP_LINK:-/var/www/beauty}"
RELEASE_BASE="${RELEASE_BASE:-/var/www/releases}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"

[ "$(id -u)" -eq 0 ] || { echo "ERRO: rode como root." >&2; exit 1; }
id "$DEPLOY_USER" >/dev/null 2>&1 || { echo "ERRO: usuário $DEPLOY_USER não existe." >&2; exit 1; }

if [ -L "$APP_LINK" ]; then
  echo "já é symlink: $(readlink -f "$APP_LINK") — nada a fazer."
  exit 0
fi
[ -d "$APP_LINK" ] || { echo "ERRO: $APP_LINK não existe." >&2; exit 1; }

install -d -o "$DEPLOY_USER" -g www-data -m 755 "$RELEASE_BASE"

ALVO="$RELEASE_BASE/beauty-inicial-$(date -u +%Y%m%d-%H%M%S)"
echo "copiando $APP_LINK -> $ALVO"
cp -a "$APP_LINK" "$ALVO"
chown -R "$DEPLOY_USER":www-data "$ALVO"

# arquivos que só existem na VPS e devem sobreviver a cada deploy
printf '%s\n' 'sql/migracoes/003_usuarios_demo.sql' > "$RELEASE_BASE/.preserve"
chown "$DEPLOY_USER":www-data "$RELEASE_BASE/.preserve"

echo "trocando $APP_LINK (diretório) por symlink"
mv "$APP_LINK" "$APP_LINK.diretorio-antigo"
ln -sfn "$ALVO" "$APP_LINK"

if [ -f "$APP_LINK/index.php" ] && [ -f "$APP_LINK/sistema/conexao.php" ]; then
  rm -rf "$APP_LINK.diretorio-antigo"
  echo "OK: $APP_LINK -> $(readlink -f "$APP_LINK")"
else
  echo "ERRO: conteúdo não encontrado depois da troca; revertendo" >&2
  rm -f "$APP_LINK"
  mv "$APP_LINK.diretorio-antigo" "$APP_LINK"
  exit 1
fi

echo
echo "Agora rode o deploy como usuário comum:  bash deploy/deploy.sh"
