# Follow-ups do Barbearia Beauty — backlog ranqueado por valor

Lista consolidada de ideias de evolução do projeto (PHP + MySQL multi-tenant SaaS para barbearias/salões), incluindo ideias inéditas (marcadas com 🆕). Ranqueadas por valor entregue. **Nenhuma foi iniciada** — este documento é o backlog para escolha.

Para cada item: descrição curta, **Valor** (Alto/Médio/Baixo) e **Esforço** (P=pequeno, M=médio, G=grande).

---

## 🔴 Tier 1 — Segurança e integridade de dados

1. **Sunset do MD5** — 4 de 6 usuários ainda em MD5. Forçar reset no próximo login ou período de graça. Valor: Alto. Esforço: M.
2. **Tokens CSRF em todos os formulários de mutação** — hoje não existem. Um link malicioso pode disparar ações autenticadas. Valor: Alto. Esforço: M.
3. **Auditoria de XSS no painel** — vários `echo` sem escaping. Dados de cliente podem injetar script no admin. Valor: Alto. Esforço: M.
4. **Validação de uploads** — fotos sem checagem de tipo/tamanho. Risco de upload de executável e DoS. Valor: Alto. Esforço: P.
5. **Completar limpeza de SQL injection** — `listar.php`, `rel/*.php`, `confirmar.php`, `inserir-servico.php` ainda não convertidos. Valor: Alto. Esforço: M. *(em andamento)*
6. **Auditoria de isolamento multi-tenant** — verificar que todo query filtra por tenant/banco. Vazamento entre barbearias. Valor: Alto. Esforço: M.
7. **2FA para contas Administrador** — TOTP via app autenticador. Valor: Alto. Esforço: M.
8. **Audit log de ações do admin** — quem criou/excluiu/mudou o quê e quando. Valor: Alto. Esforço: M.
9. **Fluxo "esqueci minha senha" por email** — admins precisam de reset manual hoje. Valor: Alto. Esforço: M.
10. **Rate limit em outros endpoints** — só login tem. Endpoints de busca/relatório abusáveis. Valor: Médio. Esforço: P.
11. **Gestão de segredos** — credenciais de DB no código/env sem rotação. Valor: Alto. Esforço: M.
12. **Scan de dependências** — `composer audit` / npm audit no pipeline. Valor: Médio. Esforço: P.
39. 🆕 **LGPD: consentimento, anonimização e direito ao esquecimento** — portabilidade de dados do cliente. Valor: Alto. Esforço: G.
40. 🆕 **Headers de segurança (CSP, X-Frame-Options, HSTS)** — proteção contra clickjacking/XSS. Valor: Médio. Esforço: P.
41. 🆕 **Brute-force protection / lockout de conta** — bloquear após N tentativas falhas. Valor: Alto. Esforço: P.
42. 🆕 **Histórico de login + alerta de novo login** — detectar acesso suspeito. Valor: Médio. Esforço: M.
43. 🆕 **Criptografia de dados sensíveis (CPF, telefone) at rest** — proteção em caso de dump. Valor: Alto. Esforço: G.
44. 🆕 **Criptografia de backups** — backups não podem ser lidos se vazados. Valor: Médio. Esforço: P.

## 🟠 Tier 2 — Confiabilidade e operação

13. **Migrações versionadas de schema** — schema em `sql/*.sql` solto, sem versionamento. Valor: Alto. Esforço: M.
14. **Backup automatizado do DB** — dump diário para storage externo. Valor: Alto. Esforço: M.
15. **CI/CD (GitHub Actions)** — lint PHP + testes a cada push. Valor: Alto. Esforço: M.
16. **Testes automatizados (PHPUnit)** — auth, tenant isolation, CRUD crítico. Valor: Alto. Esforço: G.
45. 🆕 **Monitoramento/observabilidade** — logs estruturados, métricas, alertas de erro (Sentry/Prometheus). Valor: Alto. Esforço: M.
46. 🆕 **Health check endpoint + uptime monitoring** — detectar queda rápido. Valor: Médio. Esforço: P.
47. 🆕 **Fila de jobs (background)** — emails/WhatsApp processados assincronamente. Valor: Médio. Esforço: G.
48. 🆕 **Cache (Redis) para sessões/config** — reduz carga no DB. Valor: Médio. Esforço: M.
49. 🆕 **Ambiente de staging separado** — testar antes de produção. Valor: Alto. Esforço: M.
50. 🆕 **Feature flags** — ligar/desligar features sem deploy. Valor: Médio. Esforço: M.
51. 🆕 **Testes E2E (Playwright/Cypress)** — fluxos críticos no browser. Valor: Alto. Esforço: G.
52. 🆕 **Disaster recovery / runbook + teste de restore** — saber recuperar em incidente. Valor: Alto. Esforço: M.

## 🟡 Tier 3 — Produto e UX

17. **Lembretes de agendamento (WhatsApp/SMS/email)** — reduz no-show. Valor: Alto. Esforço: M.
18. **Portal do cliente (autoagendamento)** — autoatendimento reduz carga e aumenta conversão. Valor: Alto. Esforço: G.
19. **Integração de pagamento (Pix/cartão)** — fecha o ciclo de recebimento. Valor: Alto. Esforço: G.
20. **Dashboard de métricas** — faturamento, ocupação, ticket médio, top serviços. Valor: Alto. Esforço: M.
21. **Alertas de estoque baixo** — notifica o admin. Valor: Médio. Esforço: P.
22. **Cartão fidelidade digital** — contagem + resgate. Valor: Médio. Esforço: M.
23. **Comissões automáticas** — fechamento mensal e relatório. Valor: Médio. Esforço: M.
24. ✨ **Modo "manutenção" por tenant** — desabilitar agendamentos sem tirar o site do ar. Valor: Baixo. Esforço: P.
25. ✨ **Multi-unidade (franquia)** — uma conta gerindo várias barbearias. Valor: Alto. Esforço: G.
26. ✨ **Avaliação pós-atendimento (NPS)** — link no WhatsApp após o serviço. Valor: Médio. Esforço: M.
27. ✨ **Lista de espera** — fila quando horário lotado, notificação de cancelamento. Valor: Médio. Esforço: M.
28. ✨ **Precificação dinâmica** — preço por horário e por barbeiro sênior. Valor: Médio. Esforço: M.
29. ✨ **Exportação fiscal** — NF-e/NFS-e para contabilidade. Valor: Alto. Esforço: G.
30. ✨ **Integração com Instagram/Google Reviews** — puxar avaliações para o site. Valor: Médio. Esforço: M.
31. ✨ **Onboarding guiado** — wizard de configuração inicial. Valor: Médio. Esforço: M.
53. 🆕 **Agendamento recorrente** — semanal/mensal para clientes fixos. Valor: Alto. Esforço: M.
54. 🆕 **Fila de espera / walk-in** — check-in sem agendamento, chamada por ordem. Valor: Médio. Esforço: M.
55. 🆕 **Reagendamento/cancelamento self-service** — link para o cliente alterar sozinho. Valor: Alto. Esforço: M.
56. 🆕 **Histórico de atendimentos por cliente** — ficha técnica (corte preferido, produtos). Valor: Alto. Esforço: M.
57. 🆕 **Fotos antes/depois por atendimento** — portfólio por cliente/barbeiro. Valor: Médio. Esforço: M.
58. 🆕 **Bloqueio de horários** — férias, almoço, folga por barbeiro. Valor: Alto. Esforço: P.
59. 🆕 **Programa de indicação (referral)** — desconto para quem indica. Valor: Médio. Esforço: M.
60. 🆕 **Aniversário do cliente** — mensagem + desconto automático. Valor: Médio. Esforço: P.
61. 🆕 **Cupons e promoções por horário** — happy hour, descontos. Valor: Médio. Esforço: M.
62. 🆕 **Combos/pacotes de serviços** — venda casada com desconto. Valor: Médio. Esforço: M.
63. 🆕 **Galeria de cortes (portfólio público) + SEO local** — atrai novos clientes. Valor: Alto. Esforço: M.
64. 🆕 **Chatbot WhatsApp para agendamento** — agendamento self-service via conversa. Valor: Alto. Esforço: G.
65. 🆕 **Notificações push (web push)** — lembretes no navegador. Valor: Médio. Esforço: M.
66. 🆕 **Assinatura/planos SaaS (billing dos tenants)** — cobrança mensal. Valor: Alto. Esforço: G.
67. 🆕 **Branding white-label por tenant** — logo, cores, domínio customizado. Valor: Alto. Esforço: G.
68. 🆕 **Controle de caixa** — abertura/fechamento, sangria, conferência. Valor: Alto. Esforço: M.

## 🟢 Tier 4 — Polimento

32. **Performance: N+1 e queries pesadas** — JOINs; cachear config. Valor: Médio. Esforço: M.
33. **Acessibilidade (WCAG)** — contraste, labels, teclado. Valor: Médio. Esforço: M.
34. **PWA / offline para staff** — agenda funciona sem internet. Valor: Médio. Esforço: G.
35. **Otimização de imagens** — WebP, lazy loading, redimensionamento. Valor: Baixo. Esforço: P.
36. **i18n** — hoje só pt-BR. Valor: Baixo. Esforço: G.
37. **API REST documentada** — para app mobile futuro (OpenAPI). Valor: Médio. Esforço: M.
38. **Soft delete** — exclusão física perde histórico. Valor: Médio. Esforço: M.
69. 🆕 **Exportação de relatórios (CSV/PDF/Excel)** — para análise externa. Valor: Médio. Esforço: M.
70. 🆕 **Importação de dados (CSV)** — migração de outros sistemas. Valor: Médio. Esforço: M.
71. 🆕 **Atalhos de teclado + busca global no painel** — produtividade do admin. Valor: Baixo. Esforço: M.
72. 🆕 **Filtros salvos / views personalizadas** — listas customizáveis. Valor: Baixo. Esforço: M.
73. 🆕 **Comparativo de período anterior** — relatórios com % de variação. Valor: Médio. Esforço: P.
74. 🆕 **RBAC completo** — roles e permissões granulares. Valor: Alto. Esforço: G.
75. 🆕 **Webhooks para integrações externas** — notificar sistemas externos. Valor: Médio. Esforço: M.
76. 🆕 **Integração com Zapier/Make/n8n** — automações no-code. Valor: Médio. Esforço: M.

---

## Ordem sugerida de execução

- **Sprint 1 (segurança):** itens 1–6 + 9 + 40 + 41 + 42 + 43.
- **Sprint 2 (confiabilidade):** itens 13–16 + 10–11 + 45 + 46 + 47 + 49 + 50 + 52.
- **Sprint 3 (produto):** itens 17–19 + 22 + 24 + 53 + 55 + 56 + 60 + 63 + 64 + 66 + 67 + 68.

Nenhum item foi iniciado. Diga qual quer que eu ataque primeiro.
