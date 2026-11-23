# 💈 Barbearia PRO — Plano de Transformação em SaaS

> Documento vivo, construído a partir da **entrevista** com o dono do produto.
> Última atualização: 2026-08-11
>
> **Status**: Fase 0 ✅ · Fase 1 🟡 (auth + onboarding + superadmin) · Fase 2 e 5 🟡 (wizard grava no banco + site por tenant) · Fases 3, 4, 6–7 pendentes.
> Estratégia de lançamento: **site público + wizard de agendamento = produto nº 1** (o que gera receita); o painel completo é a fase de retenção (ver "🚀 Estratégia de lançamento").
> Nota: o frontend é **Next.js 16** (React 19) — o Next não tem versão "19" (o 19 é do React).

## 🎯 Decisões da entrevista

| Tema | Decisão |
|---|---|
| **Migração** | Reescrita total: frontend **React 19** + backend **Node.js (API)** substituindo o PHP |
| **Frontend** | **Next.js 16** (React 19, App Router, SSR/SSG p/ SEO) no lugar do Vite SPA; Astro é a alternativa leve só p/ a landing |
| **API interna** | **tRPC** sobre Fastify — tipos fim-a-fim sem codegen |
| **Estratégia** | **Domain-first** — extrair regras de negócio (agendamento, comissões, financeiro) com testes ANTES de auth/UI |
| **Multi-tenant** | **Subdomínio por barbearia** (ex.: `sua-barbearia.minhasaas.com`) |
| **Monetização** | **Freemium**: plano grátis + planos pagos por assinatura |
| **Quotas** | Limites por plano enforçados no `packages/domain` desde o início (`plans.ts`) — contagem de uso na hora da ação, não em job |
| **Superadmin** | Role `saas_admin` com `BYPASSRLS` + app interno `/admin` — sem isso o RLS cega o operador do SaaS |
| **Importação** | Migração MySQL→Postgres tratada como **ferramenta de venda** ("migre sua barbearia em 5 min") — spike antes do lançamento do produto 1 |
| **Escopo** | Migrar **todo o sistema** (agendamento, painel, financeiro, estoque, comissões, site) |

## 🏗️ Arquitetura alvo

```
monorepo (pnpm workspaces)
├── apps/
│   ├── web/              # Frontend Next.js 16 (React 19) — landing + cliente + painel (SSR p/ SEO)
│   └── api/              # Backend Node (Fastify + TypeScript) — superfície tRPC
├── packages/
│   ├── ui/               # Design system (tokens gerados pelo skill design-system)
│   ├── db/               # Schema Prisma/Drizzle + migrações + seed
│   └── domain/           # Regras de negócio compartilhadas (agendamento, planos) — funções puras + testes de invariante
└── infra/                # Docker, Railway config, CI
```

### Stack (pesquisado e recomendado)

| Camada | Serviço | Por quê |
|---|---|---|
| **Banco** | [Neon](https://neon.tech) (Postgres serverless) | Free tier generoso, autoscaling, branching p/ staging; ideal p/ multi-tenant |
| **Frontend** | [Next.js 16](https://nextjs.org) (React 19, App Router + RSC) | SEO p/ o site público por tenant + painel no mesmo app; Astro como alternativa leve p/ landing |
| **API interna** | **tRPC** sobre Fastify | Tipos fim-a-fim sem codegen p/ os ~30 módulos do painel; REST público só p/ integrações |
| **Auth** | [Clerk](https://clerk.com) (orgs B2B) | Login e-mail/senha + social, sessões seguras, **organizações** (o "tenant") com free tier |
| **Pagamentos** | [Stripe](https://stripe.com/br) | Assinaturas + **Pix** e cartão no Brasil; webhooks de billing |
| **Deploy** | Railway (MCP já configurado no projeto) | PHP atual → futuro: API + web + Postgres no mesmo lugar |
| **Notificações** | WhatsApp (existente) + e-mail transacional | Manter integração de agendamento por WhatsApp |
| **Arquivos** | Cloudflare R2 (S3-compatível) | Imagens de produtos/serviços/avatares — hoje servidas do disco local; em multi-tenant precisa de bucket |
| **Relatórios (PDF)** | pdfmake (ou Puppeteer p/ HTML→PDF) | Substitui o DOMPDF do PHP |
| **Observabilidade** | Sentry + logs estruturados | Com pagamentos no meio, erro de produção precisa de rastreio desde o início |

> Alternativas avaliadas: Turso (SQLite edge — não para dados relacionais pesados do financeiro),
> better-auth (self-hosted, sem custo — opção se quiser fugir do Clerk), LemonSqueezy (não tem Pix).

## 🧱 Domain-first (antes de auth e UI)

A regra de negócio mais sensível hoje está espalhada em arquivos PHP monolíticos
(`app/agendamentos.php` tem ~2.000 linhas; lógica nos scripts de `app/ajax/`). Antes de
qualquer auth/UI, portar para `packages/domain` como **funções puras TypeScript com
testes de invariante** (Vitest):

- **Agendamento**: nunca dois horários sobrepostos por profissional; regras de dias/horários por funcionário.
- **Comissões**: cálculo por serviço/funcionário — o que "erra dinheiro" se quebrar.
- **Financeiro**: entradas/saídas e limites por plano (freemium).

A API (tRPC) e o frontend consomem esse domínio — nada de lógica de negócio em componente ou rota.

## 🧩 Multi-tenant (subdomínio)

```
client.solicita → api resolve subdomínio → tenant (barbearia) → escopo em TODAS as queries
```

- **Resolução**: middleware lê `Host`/subdomínio → busca `tenants.slug` (cache Redis) → injeta `tenantId` no request.
- **Isolamento**: coluna `tenant_id` em todas as tabelas **+ Row-Level Security (RLS)** no Postgres como segunda barreira (o padrão de segurança para SaaS).
- **Wildcard**: `*.minhasaas.com` (Railway/Cloudflare) aponta para o mesmo app.
- **Domínio próprio** (cliente premium): campo `custom_domain` + verificação (planos Pro+).

## 🆓 Modelo freemium (proposta inicial — validar na entrevista 2)

| Recurso | Grátis | Pro | Premium |
|---|---|---|---|
| Agendamentos/mês | 50 | 500 | ilimitado |
| Funcionários | 2 | 5 | ilimitado |
| Financeiro + comissões | — | ✔ | ✔ |
| Estoque + produtos | — | — | ✔ |
| Domínio próprio | — | — | ✔ |
| WhatsApp (agendamento) | ✔ | ✔ | ✔ |
| **Preço sugerido (R$)** | R$ 0 | R$ 49/mês | R$ 99/mês |

*Preços são sugestão — ajustar na próxima entrevista.*

## 🚀 Estratégia de lançamento (produto nº 1)

**O produto vendável é o site público por tenant + agendamento online**, não o painel:

1. **Produto 1** (lança primeiro — gera receita): site da barbearia (SEO) + wizard de agendamento + confirmação WhatsApp, com dados vindos do banco e onboarding com dados de demonstração.
2. **Retenção** (vem depois): painel completo em React — agenda visual, clientes, financeiro, comissões, fidelidade.
3. **Conversão**: importador MySQL→Postgres como ferramenta de venda — "migre sua barbearia em 5 minutos" (o legado tem o dado ouro: clientes, fidelidade, histórico financeiro).
4. Enquanto o painel React não existe, o painel PHP legado continua servindo o back-office dos primeiros clientes (vira o "backend" temporário — não é reescrito de uma vez).

## 🗺️ Roadmap (migração de todo o sistema)

### Fase 0 — Fundação ✅
- [x] ✅ Scaffold do monorepo (pnpm workspaces): `apps/web` (Next.js) + `apps/api` (Fastify + tRPC) + `packages/*`
- [x] ✅ Design tokens (skill **design-system**: primitive → semantic → component) na identidade dark + dourado (`packages/ui`)
- [x] ✅ CI básico + `npm audit` no fluxo (`.github/workflows/ci.yml`) — falta repositório git/GitHub p/ rodar
- [x] ✅ Schema MySQL (21 tabelas) → Postgres multi-tenant em `packages/db`: `tenant_id` + RLS (21 policies), 49 FKs, unique do slot de agendamento, identidade `GENERATED BY DEFAULT` p/ migração
- [x] ✅ **Domain-first**: agendamento, comissão, conclusão de serviço e **financeiro** portados p/ `packages/domain` (59 testes de invariante, incluindo baixa de receber → comissão e totais)
- [x] ✅ Testes E2E smoke (Playwright, 9 testes) nas páginas críticas do site por tenant
- [ ] ⬜ Sentry/observabilidade + deploy do CI (falta repositório git/GitHub)

### Fase 1 — Identidade, auth e tenant
- [x] ✅ Clerk (orgs B2B) integrado globalmente — `<ClerkProvider>`, `clerkMiddleware` no `proxy.ts`, rotas `/sign-in`/`/sign-up`, menu com `UserButton` + `OrganizationSwitcher` (org = tenant), guard de `saas_admin` no `/admin`; Organizations habilitadas na instância (modo B2B, `force_organization_selection`); chaves preenchidas (dev)
- [ ] 🟡 Migração de senhas MD5 → Clerk — módulo `packages/domain/src/password.ts` ✅ (cascata MD5→bcrypt, `deveMigrar`, testado contra o hash real do dump); falta o login legado/convite (painel PHP serve enquanto isso)
- [x] ✅ Resolução de subdomínio + onboarding — subdomínio ✅ (`proxy.ts`) e **onboarding funcional no dev**: `garantirTenantDaOrg` (org → tenant com dados demo, slug único, limite da org via API do Clerk) disparado na home; ⬜ em produção (webhook `organization.created` na API)
- [x] ✅ Camada RLS/tenant_id no banco (schema + `sql/02-enable-rls.sql`)
- [ ] 🟡 **Superadmin do SaaS**: role `saas_admin` com `BYPASSRLS` (`sql/03-saas-admin.sql`) + página `/admin` com auth ✅ + script `admin:promover` (public_metadata.role) ✅; falta status de assinatura

### Fase 2 — Agendamento online (o coração) 🟡
- [x] ✅ Wizard de agendamento (dados → profissional → horário → serviço → resumo) consumindo o domain — rota `/agendar` por tenant, conflito em tempo real via `horariosDisponiveis`/`reservarSlot`
- [x] ✅ Regras de horários/dias por funcionário + conflitos (invariante da tripla data+hora+funcionário)
- [x] ✅ **Agendamento salvo no banco** (`lib/agendar.ts` + server action): revalida o slot com `reservarSlot` contra o banco no momento da ação, cliente por telefone (upsert), INSERT com UNIQUE de slot (corrida mapeada para erro amigável) e estado de sucesso no wizard; isolado por tenant (testado). ⚠️ formato dev (pg-mem); produção via API (Fase 7)
- [ ] ⬜ Confirmação via WhatsApp (wizard gera o link com a mensagem completa e mostra após salvar; fluxo de confirmação/status pendente)
- [x] ✅ Dados vindos do banco — `lib/tenants.ts` (mock) substituído por repositório (`lib/repo.ts`) sobre `packages/db`: dev roda **pg-mem** com seed do dump real via `@barbearia/import`; produção usa Postgres (Neon) com RLS
- [x] ✅ Onboarding com dados de demonstração — `garantirTenantDaOrg` provisiona catálogo real do legado + 2 barbeiros (limite Grátis) + agendamentos demo no dev

### Fase 3 — Painel administrativo
- [ ] ⬜ Agenda visual do dia/semana, clientes, funcionários, cargos
- [ ] ⬜ Comentários/retornos, fidelidade (cartões — regra de cartão já no domain, UI do painel não)

### Fase 4 — Financeiro, comissões e estoque
- [ ] ⬜ Módulos do painel (contas a pagar/receber, vendas, entradas/saídas) consumindo o domain de financeiro + relatórios PDF (pdfmake/Puppeteer)
- [ ] 🟡 Comissões por serviço/funcionário ✅ no domain; estoque de produtos e UI ⬜

### Fase 5 — Site público por tenant 🟡
- [ ] 🟡 Landing + serviços + produtos + agendamento no subdomínio de cada barbearia (SSR/SSG p/ SEO) — ✅ hero, serviços, produtos, rodapé, SEO/metadata/JSON-LD, sitemap, proxy por subdomínio e **dados vindos do banco** (repo + pg-mem dev / Postgres prod); faltam depoimentos, contato e personalização de tema por tenant

### Fase 6 — Billing freemium
- [x] ✅ Limites por plano definidos no domain (`packages/domain/src/plans.ts`: agendamentos/mês, funcionários, financeiro, estoque, domínio próprio) — enforcement na API/UI ⬜
- [ ] ⬜ Stripe: planos, checkout, portal de cobrança, webhooks (Pix + cartão)
- [ ] ⬜ Enforcar limites na ação (API + UI) usando `plans.ts`

### Fase 7 — Migração de dados e deploy
- [ ] 🟡 Importador MySQL → Postgres — spike em `packages/import` (parser do dump + mapeamento puro com remap de sentinelas, testado contra o `sql/barbearia.sql` real); faltam leitor via MySQL ao vivo e gravação no Postgres
- [ ] ⬜ Deploy no Railway (web + api + Postgres Neon) com domínio wildcard + CDN no site público por tenant
- [ ] ⬜ Corte do PHP (documentação de descontinuação)

## 🔒 Segurança (skill security-and-hardening aplicado)

- Senhas com bcrypt/argon2; sessões httpOnly + secure + sameSite
- Migração MD5 → Clerk: **nunca** importar hash MD5 como se fosse seguro; todo usuário legado passa por convite/redefinição de senha no primeiro login
- Validação de entrada com Zod em toda rota da API (fronteira)
- Queries sempre parametrizadas (Prisma/Drizzle) — sem concatenação
- Rate limiting em `/auth` e na API geral
- Headers de segurança (helmet + CSP), CORS restrito aos domínios conhecidos
- Isolamento de tenant verificado em TODA rota (nunca confiar só no subdomínio)
- Nunca logar dados sensíveis; segredos só em variáveis de ambiente
- `npm audit` antes de cada release; revisar dependências novas (typosquatting)

## 🎨 UI/UX (skill design-system aplicado)

- Tokens em 3 camadas (primitive → semantic → component) gerados por script
- CSS variables para tema escuro/claro (já usamos vars no PHP — migrar o conceito)
- Componentes com estados definidos (hover/active/disabled) — especificações no `packages/ui`
- Validador de tokens no CI (impede hex hardcoded nos componentes)

## ✅ Próximo passo

**Fase 0 fechada**; **Fase 1 quase completa** (auth + onboarding + superadmin funcionais no dev);
**produto nº 1 com ciclo fechado**: o wizard **grava o agendamento no banco** e o site lê dados reais.
A sequência agora é:
1. **Fase 7 (deploy/conversão)** — Postgres real + deploy (web+api no Railway) e o provisionamento/escrita em produção via API (webhook `organization.created` + agendamento com ids gerados);
2. **Fase 2→5 (produto 1)** — fluxo de confirmação WhatsApp/status e depoimentos/contato;
3. **Fase 3 (retenção)** — painel mínimo: agenda do dia (confirmar/baixar os agendamentos online que o banco já recebe);
4. **Fase 6 (receita)** — Stripe + enforcement de `plans.ts` na ação.

O painel completo (Fase 3) e o restante da Fase 4 vêm depois, como retenção — o painel PHP
segue servindo o back-office enquanto isso.
