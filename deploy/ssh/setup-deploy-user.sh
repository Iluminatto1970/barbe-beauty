#!/usr/bin/env bash
# Cria/atualiza o usuário de deploy da VPS (idempotente).
# Rodar como root NA VPS:  bash setup-deploy-user.sh
#
# O usuário:
#  - entra somente por chave (a mesma que já funciona para root, marcada
#    com o comentário abaixo);
#  - escreve no docroot /var/www/beauty por ACL (sem ser root);
#  - recarrega o Caddy e o PHP-FPM por sudoers sem senha (comandos fixos);
#  - lê o journal (grupo systemd-journal).
set -euo pipefail

USER_NAME="${DEPLOY_USER:-deploy}"
PUBKEY_FILE="${PUBKEY_FILE:-/root/.ssh/authorized_keys}"
PUBKEY_MARKER="${PUBKEY_MARKER:-omnistack-notebook}"

if ! id "$USER_NAME" >/dev/null 2>&1; then
  useradd --create-home --shell /bin/bash "$USER_NAME"
  echo "usuário $USER_NAME criado"
else
  echo "usuário $USER_NAME já existe"
fi

install -d -m 700 -o "$USER_NAME" -g "$USER_NAME" "/home/$USER_NAME/.ssh"

KEY_LINE="$(grep -F " $PUBKEY_MARKER" "$PUBKEY_FILE" | head -1 || true)"
if [ -z "$KEY_LINE" ]; then
  echo "ERRO: nenhuma chave com marcador '$PUBKEY_MARKER' em $PUBKEY_FILE" >&2
  exit 1
fi

AUTH="/home/$USER_NAME/.ssh/authorized_keys"
touch "$AUTH"
if ! grep -qF "$KEY_LINE" "$AUTH"; then
  printf '%s\n' "$KEY_LINE" >> "$AUTH"
  echo "chave adicionada em $AUTH"
else
  echo "chave já presente em $AUTH"
fi
chown "$USER_NAME:$USER_NAME" "$AUTH"
chmod 600 "$AUTH"

# Sudoers mínimo: só os comandos de deploy, sem senha.
cat > "/etc/sudoers.d/$USER_NAME" <<EOF
$USER_NAME ALL=(root) NOPASSWD: /usr/bin/systemctl reload caddy, \\
    /usr/bin/systemctl restart caddy, \\
    /usr/bin/systemctl restart php8.4-fpm, \\
    /usr/bin/systemctl restart beauty-tenants.service, \\
    /usr/bin/systemctl stop ssh-unlock-guard.timer
EOF
chmod 440 "/etc/sudoers.d/$USER_NAME"
visudo -cf "/etc/sudoers.d/$USER_NAME" >/dev/null
echo "sudoers: /etc/sudoers.d/$USER_NAME"

# Escrita no docroot via ACL (arquivos e diretórios), sem virar root.
if [ -d /var/www/beauty ]; then
  setfacl -R -m "u:$USER_NAME:rwX" /var/www/beauty
  find /var/www/beauty -type d -exec setfacl -d -m "u:$USER_NAME:rwX" {} +
  echo "ACL aplicada em /var/www/beauty"
fi

# Caddyfile editável pelo deploy (site/config publicados por scp + reload).
if [ -f /etc/caddy/Caddyfile ]; then
  setfacl -m "u:$USER_NAME:rw" /etc/caddy/Caddyfile
fi
if [ -d /etc/caddy/sites ]; then
  find /etc/caddy/sites -type d -exec setfacl -d -m "u:$USER_NAME:rw" {} +
fi

usermod -aG systemd-journal "$USER_NAME" 2>/dev/null || true

echo
echo "OK. Grupos de $USER_NAME: $(id -nG "$USER_NAME")"
