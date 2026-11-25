#!/usr/bin/env bash
# ============================================================
# BOOTSTRAP DO LAYOUT DE RELEASES (rodar UMA vez, COMO ROOT na VPS)
# ------------------------------------------------------------
# Converte /var/www/beauty (hoje diretório real) no docroot estável:
#
#   /var/www/beauty           -> /var/www/releases/current   (este link, root)
#   /var/www/releases/current -> /var/www/releases/beauty-inicial-<data>
#
# A partir daí o deploy troca só `current`, que fica dentro de
# /var/www/releases (do usuário deploy) — nenhum passo root é necessário.
# O docroot publicado continua sendo /var/www/beauty, então o Caddyfile
# (`root * /var/www/beauty`) e o gerador de sites dos tenants
# (/usr/local/sbin/beauty-sync-tenants.php) não mudam.
#
# Uso (na VPS, como root):  bash /home/deploy/bootstrap.sh
# ============================================================
set -euo pipefail

APP_LINK="${APP_LINK:-/var/www/beauty}"
RELEASE_BASE="${RELEASE_BASE:-/var/www/releases}"
CURRENT_LINK="${CURRENT_LINK:-$RELEASE_BASE/current}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"

[ "$(id -u)" -eq 0 ] || { echo "ERRO: rode como root." >&2; exit 1; }
id "$DEPLOY_USER" >/dev/null 2>&1 || { echo "ERRO: usuário $DEPLOY_USER não existe." >&2; exit 1; }

if [ -L "$APP_LINK" ]; then
  echo "já é symlink: $APP_LINK -> $(readlink "$APP_LINK") — nada a fazer."
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

# ponteiro que o deploy troca (o link abaixo é estável)
ln -sfn "$ALVO" "$CURRENT_LINK"
chown -h "$DEPLOY_USER":www-data "$CURRENT_LINK"

echo "trocando $APP_LINK (diretório) por symlink"
mv "$APP_LINK" "$APP_LINK.diretorio-antigo"
ln -sfn "$CURRENT_LINK" "$APP_LINK"

if [ -f "$APP_LINK/index.php" ] && [ -f "$APP_LINK/sistema/conexao.php" ]; then
  rm -rf "$APP_LINK.diretorio-antigo"
  echo "OK:"
  echo "  $APP_LINK -> $(readlink "$APP_LINK")"
  echo "  $CURRENT_LINK -> $(readlink "$CURRENT_LINK")"
  echo "  docroot real: $(readlink -f "$APP_LINK")"
else
  echo "ERRO: conteúdo não encontrado depois da troca; revertendo" >&2
  rm -f "$APP_LINK"
  mv "$APP_LINK.diretorio-antigo" "$APP_LINK"
  exit 1
fi

echo
echo "Agora, na sua máquina:  bash deploy/deploy.sh"
