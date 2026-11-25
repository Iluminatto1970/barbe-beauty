# Acesso SSH da VPS

Estado até 2026-11-25: `root` entrava **com senha** pelo IP público
(`37.27.213.38`), e havia 13 chaves em `/root/.ssh/authorized_keys`. O arquivo
`/etc/ssh/sshd_config.d/00-access.conf` (comentado como "acesso gerenciado por
`fix-ssh.sh`", script que não existe mais no servidor) é que liberava isso.

Estado atual:

| Item | Valor |
| --- | --- |
| Login por senha | **desativado** |
| Login de root | **desativado** (`PermitRootLogin no`) |
| Usuário de deploy | `deploy` (grupo `www-data`, `systemd-journal`) |
| Autenticação | somente chave (a `omnistack-notebook`, `SHA256:/775poq…`) |
| Console de emergência | console web/serial da Hetzner, com root |

## Arquivos deste diretório

- `00-access.conf` — drop-in instalado em `/etc/ssh/sshd_config.d/00-access.conf`.
  O sshd usa o **primeiro** valor que encontra, e este drop-in é incluído antes
  das diretivas de `/etc/ssh/sshd_config`: os valores daqui vencem.
- `setup-deploy-user.sh` — cria/atualiza o usuário `deploy` (idempotente):
  chave em `~deploy/.ssh/authorized_keys`, ACL de escrita em `/var/www/beauty`,
  ACL em `/etc/caddy/Caddyfile` e sudoers mínimo para recarregar Caddy/PHP-FPM.
- `apply-hardening.sh` — instala o drop-in **com rollback automático**.

## Instalar (como root na VPS)

```bash
scp -r deploy/ssh root@vps:/root/deploy-ssh
ssh root@vps 'bash /root/deploy-ssh/setup-deploy-user.sh'
ssh root@vps 'bash /root/deploy-ssh/apply-hardening.sh /root/deploy-ssh/00-access.conf'
```

### Rede de segurança (importante)

`apply-hardening.sh` salva `/etc/ssh` em `/root/ssh-hardening-backup-<data>`
e agenda, via `systemd-run`, a restauração desse backup em **10 minutos**
(`GRACE_MIN`). Ele só recarrega o sshd depois de `sshd -t` passar.

Depois de confirmar que o login como `deploy` funciona, cancele o rollback:

```bash
ssh vps 'sudo systemctl stop ssh-unlock-guard.timer'
```

Se algo der errado, basta esperar os 10 minutos (ou rodar
`/usr/local/sbin/ssh-unlock-guard.sh` como root) para voltar à configuração
anterior, com root por chave e senha habilitada.

## Deploy do dia a dia

O usuário `deploy` escreve no docroot por ACL — não precisa de root:

```bash
scp arquivo.php deploy@vps:/var/www/beauty/
ssh vps 'caddy validate --config /etc/caddy/Caddyfile && sudo systemctl reload caddy'
```

## O que mudou para quem usava root

Qualquer máquina/agente que ainda entre como `root` (as outras 12 chaves de
`/root/.ssh/authorized_keys`) **perde o acesso por SSH**. Para reabilitar
temporariamente, como root no console da Hetzner:

```bash
sed -i 's/^PermitRootLogin no/PermitRootLogin prohibit-password/' \
  /etc/ssh/sshd_config.d/00-access.conf
sshd -t && systemctl reload ssh
```

O correto, porém, é instalar a chave daquela máquina em
`/home/deploy/.ssh/authorized_keys` e passar a usar `deploy`.
