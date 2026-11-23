# 🚂 MCP do Railway

O projeto já vem com o **Railway MCP** configurado (endpoint remoto `https://mcp.railway.com`).
Ele permite gerenciar o Railway (projetos, banco de dados, variáveis, deploys) direto do seu
assistente de IA / editor.

---

## 📄 Arquivos de configuração já incluídos

| Arquivo          | Cliente                    |
|------------------|----------------------------|
| `.mcp.json`      | Cursor, Claude Code, Cline, etc. |
| `.cursor/mcp.json` | Cursor (específico do projeto) |
| `.vscode/mcp.json` | VS Code (com extensão de MCP / Copilot Chat) |

## 🔐 Autenticação (uma vez por cliente)

O MCP remoto usa **OAuth no navegador**:

1. Abra o projeto no seu editor (Cursor / VS Code / Claude Code).
2. Quando a ferramenta do Railway for chamada, um popup do navegador abre.
3. Faça login na sua conta Railway e autorize o acesso.
4. Pronto — o token fica salvo no cliente.

## 🖥️ Ativar em cada cliente

**Cursor** — as configs `.mcp.json` são detectadas automaticamente. Confirme em
`Settings → MCP` que o servidor `railway` está conectado (ícone verde).

**VS Code** — instale a extensão **GitHub Copilot Chat** (ou outra com suporte a MCP);
o arquivo `.vscode/mcp.json` é lido automaticamente.

**Claude Code (CLI):**

```bash
# Remoto (OAuth no navegador)
claude mcp add railway --transport http https://mcp.railway.com

# ou Local (via CLI do Railway, usa seu login da CLI)
railway setup agent
```

**Alternativa local (CLI do Railway):**

```bash
npm install -g @railway/cli
railway login
railway mcp install          # detecta e configura o cliente instalado
railway setup agent          # ou bootstrap manual
```

## 🧰 Ferramentas disponíveis (resumo)

- **Conta:** `whoami`
- **Projetos/Serviços:** `list-projects`, `create-project`, `list-services`
- **Variáveis:** `list-variables`, `set-variables`
- **Deploys:** `deploy`, `redeploy`, `accept-deploy`, `get-logs`
- **Domínios:** `generate-domain`
- **Agente Railway:** `railway-agent` (análise avançada de logs/infra)

> ⚠️ Ferramentas de deploy (`redeploy`, `accept-deploy`) são marcadas como destrutivas
> e pedem confirmação no cliente antes de executar.

## 🚀 Quando quiser publicar no Railway

O projeto **já está preparado para deploy** (conexão por variáveis de ambiente).
Passo a passo sugerido (via MCP ou dashboard):

1. **Login:** `whoami` → autorize o OAuth se pedir.
2. **Criar projeto:** `create-project` (nome: `barbearia-pro`).
3. **Adicionar MySQL:** no dashboard do projeto → *New → Database → MySQL*.
   Copie a `MYSQL_URL` gerada.
4. **Definir variáveis** no serviço web:

   ```
   MYSQL_URL=<url do banco criado>
   APP_BASE_PATH=
   SHOW_DB_ERROR=0
   ```

5. **Importar o banco:** suba o serviço web e importe `sql/barbearia.sql`
   (no phpMyAdmin do MySQL do Railway ou via `railway` CLI / MCP).
   > 💡 Posso criar um script de seed automático (`docker/seed.php`) que importa o
   > `barbearia.sql` na primeira execução se o banco estiver vazio — basta pedir.
6. **Deploy:** conecte o repositório (GitHub) ou rode `railway up` a partir da pasta `app/`.
7. **Domínio:** `generate-domain` e acesse `https://<seu-dominio>.up.railway.app`.

## 🔒 Segurança

- Nunca suba `.env` com credenciais reais (já está no `.gitignore`).
- Em produção, defina `SHOW_DB_ERROR=0` (padrão).
- Troque a senha padrão do usuário admin (`admin@admin` / `123`) após o primeiro acesso.
