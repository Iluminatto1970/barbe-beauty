# Caddy da VPS (iluminatto.online e beauty.iluminatto.online)

`Caddyfile` neste diretório é uma **cópia fiel** do arquivo em produção:
`/etc/caddy/Caddyfile` na VPS (`vps`, `37.27.213.38`). Não é uma versão
idealizada — se o servidor mudar, atualize este arquivo com o mesmo conteúdo.

## Dominio principal do SaaS

A raiz `iluminatto.online` (e `www`) serve a plataforma: landing, login,
planos, autocadastro e super admin, tudo no banco de controle. Cada
estabelecimento fica no seu subdominio `{slug}.iluminatto.online`, que o
`beauty-sync-tenants.php` publica em `/etc/caddy/sites/`. O HTTP da raiz
redireciona para HTTPS (301).

Para a raiz sair do provedor antigo e passar a responder aqui, o DNS precisa
apontar o **A de `@`** para `37.27.213.38` (hoje aponta para a Vercel, que
segue atendendo o `www` se ele nao for alterado). O certificado do dominio
raiz e emitido automaticamente pelo Caddy assim que o DNS propagar.

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
