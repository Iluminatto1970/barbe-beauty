# 💈 Barbearia PRO — Gestão Premium para Barbearias

Sistema completo de gestão para barbearias em **PHP + MySQL**, com site público,
agendamento online e painel administrativo. Visual seguindo a identidade premium
**dark + dourado** (padrão da landing `barbeariapropremium.vercel.app`).

## 🚀 Início rápido (Docker — recomendado)

> Pré-requisito: [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e rodando.

```bash
docker compose up -d --build
```

| O quê          | URL                          | Acesso                        |
|----------------|------------------------------|-------------------------------|
| **Site**       | http://localhost:8080        | —                             |
| **Painel**     | http://localhost:8080/sistema | `admin@admin` / `123`        |
| **phpMyAdmin** | http://localhost:8081        | `barbearia` / `barbearia123` |

O banco (`sql/barbearia.sql`) é importado **automaticamente na primeira subida**.
Para parar: `docker compose down` (mantém os dados). Para zerar: `docker compose down -v`.

## ▶️ Rodar local — runtime portátil (sem instalação, já configurado)

O projeto já vem com um **runtime portátil configurado** (PHP 8.2 + MariaDB)
em `%USERPROFILE%\.local\barbearia-runtime\`, sem precisar de instalação ou admin.
O servidor embutido do PHP usa `router.php` (que replica as URLs amigáveis do
`.htaccess`) apontando para a pasta `app/`, e o banco `barbearia` já foi importado.

| Ação            | Como                                        |
|-----------------|---------------------------------------------|
| **Iniciar**     | Duplo clique em [`iniciar-local.bat`](iniciar-local.bat) |
| **Parar**       | Duplo clique em [`parar-local.bat`](parar-local.bat)     |

Após iniciar:

| O quê          | URL                          | Acesso                        |
|----------------|------------------------------|-------------------------------|
| **Site**       | http://localhost:8080        | —                             |
| **Painel**     | http://localhost:8080/sistema | `admin@admin` / `123`        |
| **phpMyAdmin** | *(não incluído no runtime portátil)* | — |

Para recriar o banco do zero (dados de exemplo incluídos):

```bash
MYSQL="$USERPROFILE/.local/barbearia-runtime/mariadb/mariadb-11.4.8-winx64/bin/mysql.exe"
"$MYSQL" -u root -e "DROP DATABASE barbearia; CREATE DATABASE barbearia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"$MYSQL" -u root barbearia < sql/barbearia.sql
"$MYSQL" --force -u root barbearia < sql/dados_ficticios.sql
```

## 🛠️ Alternativa: XAMPP clássico (PHP 7.0+ / MySQL)

1. Copie a pasta `app/` para `C:\xampp\htdocs\barbearia\`.
2. No phpMyAdmin, crie o banco `barbearia` e importe `sql/barbearia.sql`.
3. Acesse `http://localhost/barbearia` e `http://localhost/barbearia/sistema`.

> A conexão já funciona com o padrão XAMPP (`root` sem senha). Para personalizar,
> use as variáveis de ambiente abaixo.

## 🔐 Acesso padrão

- **E-mail:** `admin@admin` · **Senha:** `123` (altere após o primeiro acesso)

## ⚙️ Configuração por variáveis de ambiente

A conexão (`app/sistema/conexao.php`) lê o ambiente e cai no padrão local se nada
estiver definido. Modelo em [`.env.example`](.env.example).

| Variável        | Descrição                                          | Padrão       |
|-----------------|----------------------------------------------------|--------------|
| `DB_HOST`       | Host do MySQL/MariaDB                              | `localhost`  |
| `DB_PORT`       | Porta                                              | `3306`       |
| `DB_NAME`       | Nome do banco                                      | `barbearia`  |
| `DB_USER`       | Usuário                                            | `root`       |
| `DB_PASSWORD`   | Senha                                              | *(vazio)*    |
| `MYSQL_URL`     | URL completa (ex.: Railway) `mysql://u:s@h:3306/b` | —            |
| `APP_BASE_PATH` | Prefixo da URL (`""` = raiz, `barbearia` = XAMPP)  | auto-detecta |
| `SHOW_DB_ERROR` | `1` exibe detalhes do erro de conexão              | `0`          |

## 🧩 MCP do Railway

O servidor MCP do Railway (`https://mcp.railway.com`) já está configurado no projeto
(`.mcp.json`, `.cursor/mcp.json`, `.vscode/mcp.json`).
Veja o guia completo em **[MCP_RAILWAY.md](MCP_RAILWAY.md)** — inclui autenticação
OAuth, ativação em Cursor/VS Code/Claude Code e o passo a passo do deploy.

## 📁 Estrutura

```
├── app/                    # Aplicação (site público + painel)
│   ├── index.php           # Página inicial
│   ├── agendamentos.php    # Agendamento online
│   ├── servicos.php        # Serviços
│   ├── produtos.php        # Produtos
│   ├── css/                # Estilos (tema dark + dourado)
│   └── sistema/            # Painel administrativo + conexão
├── sql/
│   ├── barbearia.sql       # Schema + dados base (auto-import no Docker)
│   └── dados_ficticios.sql # Dados de exemplo (opcional)
├── docker/                 # Imagem PHP 8.2 + Apache (pdo_mysql, gd, rewrite)
├── docker-compose.yml      # web + mariadb + phpmyadmin
└── MCP_RAILWAY.md          # Guia do MCP Railway
```

## 📷 Imagens

A pasta `Imagens de exemplo/` contém fotos de serviços/produtos/pessoas.
As imagens usadas pelo site (`app/images/`) e pelo painel (`app/sistema/painel/img/`)
já estão nos lugares certos.

## 🧭 Roadmap

- [x] Tema premium dark + dourado (site, login e painel)
- [x] Alternador de tema escuro/claro (site e painel, preferência salva)
- [x] Layout responsivo (menu mobile, carrosséis, seções e painel)
- [x] Config por variáveis de ambiente (Docker/Railway-ready)
- [x] MCP do Railway configurado
- [x] Ambiente local com Docker (auto-seed do banco)
- [ ] Deploy no Railway (guia em `MCP_RAILWAY.md`)
- [ ] Seed automático do banco no Railway
