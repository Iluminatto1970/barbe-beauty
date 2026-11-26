# Deploy

Produção (`beauty.iluminatto.online`, `/var/www/beauty` na VPS) **não é um
checkout git** e até 2026-11-25 era atualizada com `scp` manual, arquivo por
arquivo. Agora cada deploy publica um commit inteiro numa release datada e
imutável, com troca atômica de symlink, smoke das rotas críticas e rollback
automático.

## Layout

```
/var/www/beauty           -> /var/www/releases/current      link estável (criado 1x, root)
/var/www/releases/current -> /var/www/releases/beauty-…      trocado a cada deploy (atômico)
/var/www/releases/beauty-20261125-153000-ef30853/            release (git archive do commit)
/var/www/releases/beauty-…/                                  releases anteriores (rollback)
/var/www/releases/.preserve                                  arquivos só do servidor
/var/www/releases/deploy.log                                 histórico de deploys
```

São dois níveis de symlink de propósito: `/var/www` é do root, então o link
final (`/var/www/beauty`) é criado uma única vez no bootstrap, e a troca de
release acontece em `current`, dentro de `/var/www/releases` (do usuário
`deploy`) — por isso o deploy do dia a dia não pede root.

O docroot publicado continua sendo `/var/www/beauty`, então o `Caddyfile`
(`root * /var/www/beauty`), o gerador de sites dos tenants
(`/usr/local/sbin/beauty-sync-tenants.php`) e qualquer caminho absoluto do
projeto seguem válidos.

`deploy/` e `.github/` têm `export-ignore` no `.gitattributes`: infraestrutura
e documentação não vão para o docroot (por isso o smoke espera 404 em
`/deploy/`).

## Primeira vez (uma única vez, como root na VPS)

`/var/www` é do root, então a conversão do docroot em symlink exige root:

```bash
scp deploy/bootstrap.sh deploy@vps:/home/deploy/
# no console do provedor (Hetzner), como root:
bash /home/deploy/bootstrap.sh
```

Ele copia o conteúdo atual para `/var/www/releases/beauty-inicial-<data>`,
troca `/var/www/beauty` pelo symlink e só então remove o diretório antigo.

## Deploy do dia a dia (usuário `deploy`, sem root)

```bash
bash deploy/deploy.sh              # publica o HEAD
REF=v1.2.3 bash deploy/deploy.sh   # publica outra ref
bash deploy/deploy.sh rollback     # volta para a release anterior
```

O script:

1. recusa se `/var/www/beauty` ainda não for symlink (bootstrap pendente);
2. extrai `git archive $REF` para `/var/www/releases/.incoming` — só conteúdo
   commitado, sem `.env`, `.git`, `node_modules`;
3. copia do release atual os arquivos listados em `.preserve`;
4. libera escrita do PHP (`www-data`) em `images/`, `sistema/img/` e
   `sistema/painel/img/` (ACL recursiva + default, ou `chgrp`/`chmod` se não
   houver `setfacl`). A release sai do `git archive`, então nasce `644/755` do
   usuário `deploy`: sem esse passo o upload de logo/banner/foto do painel
   falharia em silêncio (`move_uploaded_file` sem permissão). Ajuste a lista em
   `WRITABLE_DIRS` se o app passar a gravar em outro diretório;
5. troca o symlink com `ln -sfn` + `mv -Tf` (rename atômico) e reinicia o
   PHP-FPM para limpar OPcache/realpath cache;
6. roda o smoke em `/`, `/landing.php`, `/sistema/`, `/css/style.css`,
   `/planos.php` (200 + conteúdo esperado) e confere que
   `/docker-compose.yml`, `/README.md`, `/deploy/deploy.sh` e
   `/deploy/bootstrap.sh` seguem 404. Caminhos com extensão sensível: um
   diretório sem extensão (`/deploy/`) cai no `try_files` do site e volta 200;
7. **se o smoke falhar, volta o symlink para a release anterior**, reinicia o
   PHP-FPM e roda o smoke de novo — saindo com código 1;
8. mantém as `KEEP` (padrão 5) releases mais recentes e registra tudo em
   `deploy.log`.

Variáveis: `SSH_HOST`, `RELEASE_BASE`, `APP_LINK`, `BASE_URL`, `REF`, `KEEP`,
`SMOKE_TENTATIVAS`, `WRITABLE_DIRS`.

## Ver o que está publicado

```bash
ssh vps 'readlink -f /var/www/beauty; tail -5 /var/www/releases/deploy.log'
```

## Outros runbooks

- `tenant-db.md` — usuário MySQL exclusivo por estabelecimento (GRANT restrito).
- `caddy/README.md` — Caddyfile e reload do Caddy.
- `ssh/README.md` — acesso SSH (usuário `deploy`, rollback de lockout).
