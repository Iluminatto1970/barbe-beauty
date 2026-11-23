/**
 * RLS (Row-Level Security) — isolamento por tenant.
 *
 * Padrão: o middleware/API define o contexto do tenant na sessão/transação e o
 * Postgres filtra TUDO pelas policies (`tenant_id = current_setting(...)`).
 * O owner do banco (migrações) ignora RLS; o app roda como `authenticated`.
 *
 * Com pooling (Neon/PgBouncer) use o escopo LOCAL à transação (3º arg `true`):
 * cada transação precisa chamar `definirTenant()` antes das queries.
 */
import { sql } from 'drizzle-orm';

/** Variável de sessão que carrega o tenant ativo. */
export const TENANT_SESSION_VAR = 'app.tenant_id';

/** Define o tenant ativo (escopo da transação atual). Ex.: `SELECT set_config(...)` */
export function definirTenant(tenantId: string) {
  return sql`SELECT set_config(${TENANT_SESSION_VAR}, ${tenantId}, true)`;
}
