# Credencial MySQL por estabelecimento

Cada cliente tem o seu banco (`barbearia_t<id>`). Antes, todas as conexões
usavam a credencial do banco de controle (`DB_USER`/`DB_PASSWORD`), que enxerga
todos os schemas: SQL injetado no site de um cliente lia os dados dos outros.

Agora o painel abre o banco do cliente com um usuário MySQL próprio, com
`GRANT` só no schema dele.

## Modelo

| item    | valor                                                                 |
|---------|-----------------------------------------------------------------------|
| usuário | `bb_t<id do tenant>` (ex.: `bb_t3`), hosts `localhost` e `127.0.0.1`   |
| senha   | `HMAC-SHA256(chave, "barbearia-pro/tenant-db/v1/<id>")`, 40 chars hex  |
| GRANT   | `SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES, CREATE VIEW, SHOW VIEW, TRIGGER ON \`<banco>\`.*` |

A senha **não fica gravada** no banco de controle — é derivada da chave do
sistema. Ler a tabela `tenants` (por SQL injection, por backup vazado) não
entrega a credencial de nenhum cliente.

Chave usada (`sistema/tenant-db.php`, primeira que existir):

1. `TENANT_DB_KEY` no ambiente;
2. `TENANT_DB_KEY_FILE` ou `.secrets/tenant-db.key` ou
   `/var/www/beauty-secrets/tenant-db.key`;
3. `HMAC(DB_NAME, DB_PASSWORD)` — sem arquivo novo, serve como padrão.

Trocar a chave rotaciona todas as senhas no próximo provisionamento (os
usuários existentes recebem `ALTER USER`).

`tenants.db_user` guarda o nome do usuário já criado (a senha não). Vazio =
o tenant ainda entra com a credencial compartilhada.

## Provisionar

Tenant novo: `bb_tenant_provisionar` cria o usuário junto com o banco.

Tenants existentes, uma vez após o deploy (precisa de `CREATE USER`/`GRANT`;
em produção, root na VPS):

```bash
sudo php /var/www/beauty/scripts/tenant-db-usuarios.php --aplicar \
     --fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf
```

### Na VPS sem SSH de root (usuário `deploy`)

O `deploy` está no grupo `docker`, o que na prática dá root na máquina. Foi
assim que o provisionamento rodou em 2026-11-26, sem reabilitar `PermitRootLogin`:

```bash
docker run --rm --network host -v /:/host alpine \
  chroot /host php /var/www/beauty/scripts/tenant-db-usuarios.php \
  --aplicar --fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf
```

Dois detalhes que custam tempo se esquecidos:

- `--network host` é obrigatório: o pool define `DB_HOST=127.0.0.1`, que sem
  isso aponta para o loopback do container (`Connection refused`).
- o usuário de controle (`barbearia`) **não tem `CREATE USER`**. O `root` do
  MySQL local entra por socket (`unix_socket`, sem senha), então acrescente
  `-e DB_HOST=localhost -e DB_USER=root`. A senha derivada não muda, porque
  `DB_PASSWORD` continua vindo do `env[]` do pool.

O `--verificar` roda com os mesmos argumentos e confirma que cada tenant só
enxerga o próprio schema.

O painel também tenta provisionar no primeiro acesso de um tenant antigo, mas
só consegue se a credencial do banco de controle tiver `CREATE USER`. Se o
log (`/var/log/php8.4-fpm.log`) mostrar `tenant-db: usuario bb_t3 nao
provisionado: ...`, rode o comando acima e o próximo acesso já usa a
credencial dedicada.

## Conferir isolamento

```bash
sudo php /var/www/beauty/scripts/tenant-db-usuarios.php --verificar \
     --fpm-conf=/etc/php/8.4/fpm/pool.d/www.conf
```

Para cada tenant ele conecta com a credencial própria, exige que `SHOW
DATABASES` liste só o schema dele e tenta ler o `config` de outro cliente,
que tem de ser negado. Sem `--aplicar`/`--verificar` o script só mostra quem
está provisionado e quem ainda está na credencial compartilhada.

Inspeção manual de um usuário:

```sql
SHOW GRANTS FOR 'bb_t3'@'localhost';
-- GRANT USAGE ON *.* TO `bb_t3`@`localhost`
-- GRANT SELECT, INSERT, ... ON `barbearia_t3`.* TO `bb_t3`@`localhost`
```

## Se a credencial dedicada falhar

`sistema/conexao.php` tenta a credencial do tenant; se ela não conectar
(usuário removido, senha rotacionada sem provisionar), registra no log e
mantém o site no ar com a credencial compartilhada. Nenhuma senha vai para o
log. O `--verificar` acusa esses casos.

## Risco residual

A credencial compartilhada continua sendo usada para o banco de controle e
para o provisionamento (criar bancos, migrar). Quem obtiver **execução de
código** no PHP (não apenas SQL) ainda lê o ambiente do PHP-FPM. O que este
item fecha é o caminho mais barato: SQL injetado no site/painel de um cliente
agora só alcança o banco daquele cliente.
