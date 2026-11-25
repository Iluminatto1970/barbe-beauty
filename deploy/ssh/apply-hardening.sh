#!/usr/bin/env bash
# Aplica o endurecimento do SSH na VPS COM rollback automático.
# Uso, como root NA VPS:
#   bash apply-hardening.sh /root/deploy-ssh/00-access.conf
#
# Camadas de segurança contra se trancar fora:
#  1. backup completo de /etc/ssh antes de tocar em qualquer coisa;
#  2. systemd-run agenda a restauração do backup em GRACE_MIN minutos;
#  3. o reload só acontece se `sshd -t` aprovar a configuração;
#  4. depois de validar o acesso, cancele o rollback com:
#       sudo systemctl stop ssh-unlock-guard.timer
set -euo pipefail

CONF="${1:-/root/deploy-ssh/00-access.conf}"
DROPIN=/etc/ssh/sshd_config.d/00-access.conf
SSHD_CONFIG=/etc/ssh/sshd_config
GRACE_MIN="${GRACE_MIN:-10}"
TS="$(date +%Y%m%d-%H%M%S)"
BK="/root/ssh-hardening-backup-$TS"

[ -f "$CONF" ] || { echo "ERRO: $CONF não encontrado" >&2; exit 1; }

mkdir -p "$BK"
cp -a "$SSHD_CONFIG" "$BK/sshd_config"
cp -a /etc/ssh/sshd_config.d "$BK/sshd_config.d"
echo "backup salvo em $BK"

# --- rede de segurança -------------------------------------------------------
cat > /usr/local/sbin/ssh-unlock-guard.sh <<EOF
#!/usr/bin/env bash
set -euo pipefail
if [ -f /root/ssh-hardening-keep ]; then
  logger -t ssh-guard "acesso confirmado; nada a restaurar"
  exit 0
fi
cp -a "$BK/sshd_config" $SSHD_CONFIG
rm -rf /etc/ssh/sshd_config.d
cp -a "$BK/sshd_config.d" /etc/ssh/sshd_config.d
if /usr/sbin/sshd -t; then
  /usr/bin/systemctl reload ssh
  logger -t ssh-guard "configuração SSH restaurada de $BK"
else
  logger -t ssh-guard "ERRO: backup de $BK não passou em sshd -t"
fi
EOF
chmod 700 /usr/local/sbin/ssh-unlock-guard.sh

systemctl stop ssh-unlock-guard.timer 2>/dev/null || true
rm -f /root/ssh-hardening-keep
systemd-run --on-active="${GRACE_MIN}min" --unit=ssh-unlock-guard \
  --description="Rollback automático do SSH" \
  /usr/local/sbin/ssh-unlock-guard.sh >/dev/null
echo "rollback automático agendado para daqui a ${GRACE_MIN} min"

# --- configuração endurecida -------------------------------------------------
install -m 644 -o root -g root "$CONF" "$DROPIN"

# Alinha o sshd_config principal (o drop-in já vence, isto evita confusão)
sed -i -E 's/^[[:space:]]*PermitRootLogin[[:space:]]+.*/PermitRootLogin no/' "$SSHD_CONFIG"
sed -i -E 's/^[[:space:]]*PasswordAuthentication[[:space:]]+.*/PasswordAuthentication no/' "$SSHD_CONFIG"

/usr/sbin/sshd -t
echo "sshd -t: OK"
/usr/bin/systemctl reload ssh
echo "ssh recarregado"
echo
echo "Valide AGORA em outro terminal:  ssh deploy@$(hostname -I | awk '{print $1}')"
echo "Se entrar: sudo systemctl stop ssh-unlock-guard.timer"
