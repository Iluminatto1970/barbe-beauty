# Caddy da VPS (iluminatto.online e beauty.iluminatto.online)

`Caddyfile` neste diretório é uma **cópia fiel** do arquivo em produção:
`/etc/caddy/Caddyfile` na VPS (`vps`, `37.27.213.38`). Não é uma versão
idealizada — se o servidor mudar, atualize este arquivo com o mesmo conteúdo.

## Dominio principal do SaaS

O dominio principal e `beautypro.site`: a raiz serve a plataforma (landing,
login, planos, autocadastro e super admin, tudo no banco de controle). Cada
estabelecimento fica no seu subdominio `{slug}.beautypro.site`, que o
`beauty-sync-tenants.php` publica em `/etc/caddy/sites/`. O `www` e apelido:
responde 301 para a raiz, e o HTTP (80) dos dois responde 301 para
`https://beautypro.site`.

`iluminatto.online` continua atendendo a plataforma como alias (os hosts
antigos estao em `BB_HOSTS_CONTROLE`, no pool do PHP-FPM) e os subdominios
`*.iluminatto.online` dos clientes seguem no ar junto com os novos
`*.beautypro.site`, registrados em `tenant_dominios`. O dominio base vem de
`env[SA_DOMINIO_BASE]` no `/etc/php/8.4/fpm/pool.d/www.conf`, lido por
`sistema/tenant.php` (como `BB_DOMINIO_BASE`) e por `superadmin/_auth.php`
(como `SA_DOMINIO_BASE`).

### Mesmo padrao do agreste.shop

O bloco da raiz segue o desenho que ja funciona no `agreste.shop`
(`/opt/agreste/deploy/Caddyfile.agreste`): HTTPS explicito e emissao por
TLS-ALPN.

```caddyfile
https://iluminatto.online {
	tls {
		issuer acme {
			disable_http_challenge
		}
	}
	...
}
```

Motivo: o bloco global desta VPS tem `auto_https disable_redirects` e o
80 -> 443 e um `redir` fixo, entao o desafio HTTP-01 nunca chega ao Caddy.
Com `disable_http_challenge` a emissao vai pelo TLS-ALPN (443), o mesmo
caminho que ja emite os certificados do curinga. A politica e identica a do
agreste, e o Caddy as agrupa em runtime:

```bash
curl -s http://127.0.0.1:2019/config/apps/tls/automation/policies | python3 -m json.tool
# issuers: acme com challenges.http.disabled = true
# subjects: agreste.37-27-213-38.nip.io, agreste.shop,
#           www.iluminatto.online, iluminatto.online
```

### DNS (Hostinger)

A zona de `iluminatto.online` fica na Hostinger (`ns1`/`ns2.dns-parking.com`)
e e gerida pela API deles (base `developers.hostinger.com`; o antigo
`api.hostinger.com` responde 530). Token em `.secrets/hostinger.env`, fora do
git.

Registros do dominio principal novo (`beautypro.site`):

| Nome  | Tipo | Valor          | TTL |
|-------|------|----------------|-----|
| `@`   | A    | `37.27.213.38` | 300 |
| `www` | A    | `37.27.213.38` | 300 |
| `*`   | A    | `37.27.213.38` | 300 |

O curinga e obrigatorio: e ele que faz `{slug}.beautypro.site` chegar na VPS
para cada estabelecimento. O mesmo vale em `iluminatto.online`, onde `@` e
`www` tambem apontam para `37.27.213.38` (antes eram os IPs da Vercel) e os
subdominios de servico (`crm`, `beauty`, `mario`, `coolify`, `chatwoot`,
`agents`, `apolo`, `oauth`, `langfuse`) e o `TXT _sabia-verificacao.www`
foram mantidos.

```bash
set -a; . ./.secrets/hostinger.env; set +a
curl -s -H "Authorization: Bearer $HOSTINGER_API_TOKEN" \
  https://developers.hostinger.com/api/dns/v1/zones/beautypro.site
```

O `PUT` parcial nao substitui um RRset do mesmo nome: para trocar `www` de
`CNAME` para `A` e preciso `DELETE` do filtro antigo antes, e `overwrite: true`
so troca os RRs do nome+tipo enviados. Backups das zonas antes da troca:
`/root/dns-*-backup-*.json` na VPS.

## Certificados TLS (CA de producao)

O `Caddyfile` **nao** define `acme_ca` nem `acme_ca_root`: vale o padrao do
Caddy, que e o Let's Encrypt de producao. Todos os certificados em uso sao
desse emissor (intermediarias atuais `CN=YE1`/`CN=YE2`, encadeando em
`ISRG Root X2`), e o storage so tem o diretorio de producao:

```bash
# emissor realmente servido por um dominio da VPS
echo | openssl s_client -connect 127.0.0.1:443 \
  -servername beauty.iluminatto.online 2>/dev/null | openssl x509 -noout -issuer
# issuer=C=US, O=Let's Encrypt, CN=YE2   (staging mostraria "(STAGING) ...")

ls /root/.local/share/caddy/certificates/
# acme-v02.api.letsencrypt.org-directory
```

Quando um desafio falha (DNS ainda no provedor antigo, por exemplo), o
Caddy continua tentando em segundo plano e, **por projeto**, passa a usar o
staging da Let's Encrypt nas retentativas para nao estourar rate limit da
producao (ver `caddyserver.com/docs/automatic-https` > Errors). Isso gera
linhas `ca=...staging...` no log e recria a conta ACME de staging, mas
**nenhum certificado de staging e guardado**: o storage de certificados so
recebe o que for emitido com sucesso e, quando a validacao passa, a
producao ja emitiu primeiro. A prova disso e que so existe o diretorio de
producao:

```bash
find /root/.local/share/caddy/certificates -path "*staging*" -name "*.crt"
# nada
```

Emissao nova pela mesma politica (comprovada em `zz-qa-ca.iluminatto.online`,
host descartavel no curinga `*.iluminatto.online`, removido depois): HTTP 200
em 9s, `issuer=C=US, O=Let's Encrypt, CN=YE2` e cadeia
`YE2 -> Root YE -> ISRG Root X2`. Quando o `A @` de `iluminatto.online`
apontar para a VPS, o certificado da raiz sai pelo mesmo caminho.

## O que ele protege

No bloco do site (`beauty.iluminatto.online`), o docroot é a raiz do projeto
(`/var/www/beauty`), então configurações, dumps e documentação ficariam
baixáveis. O matcher abaixo responde **404** para eles:

```caddyfile
@sensivel path_regexp (?i)(^/[.][^/]+|^/(caddy|docker)file$|[.](md|ya?ml|sql|gz|zip|log|sh|bat|ini|env|json|lock|bak|old|orig|swp)$)
respond @sensivel 404
```

Isso cobre metadados do git (`.git`), `.env`, `.htaccess`, `Caddyfile`,
`Dockerfile`, `README.md`, `docker-compose.yml`, dumps e `.json`/`.lock` da
raiz. O `.htaccess` do projeto continua no repo, mas **não tem efeito** aqui:
quem serve o domínio é o Caddy (nginx/apache estão inativos).

## Publicar (na VPS)

```bash
scp deploy/caddy/Caddyfile deploy@vps:/etc/caddy/Caddyfile
ssh vps 'caddy validate --config /etc/caddy/Caddyfile && sudo systemctl reload caddy'
```

O acesso é pelo usuário `deploy` (somente chave), que tem ACL de escrita no
`Caddyfile` e sudoers para `systemctl reload caddy` — ver `../ssh/README.md`.

Valide antes de recarregar: `caddy validate` precisa dizer
`Valid configuration`. Reload é sem downtime e não derruba o TLS.

## Específico deste servidor (não portátil)

- `root * /var/www/beauty` e `php_fastcgi unix//run/php/php8.4-fpm.sock` — caminho do docroot e socket do PHP-FPM 8.4 da VPS.
- `import /opt/agreste/deploy/Caddyfile.agreste` — vhost do agreste, que mora fora deste repo.
- Bloco `crm.iluminatto.online` / `crm.claudeapi.space` — Deskcomm CRM (app em `127.0.0.1:3005`, gateway Supabase em `127.0.0.1:8000`), outro projeto.
- `on_demand_tls { ask http://127.0.0.1:4010/api/host-check }` — endpoint local de validação de hostname, só existe nessa VPS.
- `import /etc/caddy/sites/*.caddy` — sites adicionais instalados apenas no servidor.
