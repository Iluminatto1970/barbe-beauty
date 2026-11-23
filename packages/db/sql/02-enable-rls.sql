-- ============================================================
-- Ativa RLS em todas as tabelas de negócio (após `pnpm db:migrate`).
-- As policies (`tenant_isolation`) já são criadas pelo drizzle-kit a partir
-- do schema (pgPolicy). Este arquivo completa o isolamento com o
-- `ENABLE ROW LEVEL SECURITY`.
--
-- Uso: psql "$DATABASE_URL" -f sql/02-enable-rls.sql
-- ============================================================

ALTER TABLE acessos                ENABLE ROW LEVEL SECURITY;
ALTER TABLE agendamentos           ENABLE ROW LEVEL SECURITY;
ALTER TABLE cargos                 ENABLE ROW LEVEL SECURITY;
ALTER TABLE cat_produtos           ENABLE ROW LEVEL SECURITY;
ALTER TABLE cat_servicos           ENABLE ROW LEVEL SECURITY;
ALTER TABLE clientes               ENABLE ROW LEVEL SECURITY;
ALTER TABLE comentarios            ENABLE ROW LEVEL SECURITY;
ALTER TABLE config                 ENABLE ROW LEVEL SECURITY;
ALTER TABLE dias                   ENABLE ROW LEVEL SECURITY;
ALTER TABLE entradas               ENABLE ROW LEVEL SECURITY;
ALTER TABLE fornecedores           ENABLE ROW LEVEL SECURITY;
ALTER TABLE grupo_acessos          ENABLE ROW LEVEL SECURITY;
ALTER TABLE horarios               ENABLE ROW LEVEL SECURITY;
ALTER TABLE pagar                  ENABLE ROW LEVEL SECURITY;
ALTER TABLE produtos               ENABLE ROW LEVEL SECURITY;
ALTER TABLE receber                ENABLE ROW LEVEL SECURITY;
ALTER TABLE saidas                 ENABLE ROW LEVEL SECURITY;
ALTER TABLE servicos               ENABLE ROW LEVEL SECURITY;
ALTER TABLE textos_index           ENABLE ROW LEVEL SECURITY;
ALTER TABLE usuarios               ENABLE ROW LEVEL SECURITY;
ALTER TABLE usuarios_permissoes    ENABLE ROW LEVEL SECURITY;
