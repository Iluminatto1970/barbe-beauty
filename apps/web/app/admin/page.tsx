/**
 * Superadmin do SaaS — visão de TODOS os tenants (sem escopo de tenant).
 *
 * ⚠️ Segurança (produção):
 *  - esta rota precisa de auth de `saas_admin` (Clerk org/role) — TODO Fase 1;
 *  - a conexão usa a role `saas_admin` com `BYPASSRLS` (por isso consegue
 *    enxergar os dados de todos os tenants) — ver
 *    `packages/db/sql/03-saas-admin.sql`. No dev (pg-mem) não há RLS.
 */
import type { Metadata } from 'next';
import { auth } from '@clerk/nextjs/server';
import { redirect } from 'next/navigation';
import { clerkAtivo } from '../../lib/clerk';
import { listarResumoAdmin } from '../../lib/repo';

export const dynamic = 'force-dynamic';

export const metadata: Metadata = {
  title: 'Admin — Barbearia PRO',
  robots: { index: false, follow: false },
};

/**
 * Guard do superadmin: com o Clerk ativo, exige sessão com a role `saas_admin`
 * (claim custom ou role de org). Sem as chaves (dev), a página abre como antes.
 */
async function exigirSaasAdmin() {
  if (!clerkAtivo) return;
  const { userId, sessionClaims } = await auth();
  const claims = sessionClaims as {
    orgRole?: string;
    metadata?: { role?: string };
  } | null;
  const role = claims?.metadata?.role ?? claims?.orgRole;
  if (!userId) redirect('/sign-in?redirect_url=/admin');
  if (role !== 'saas_admin') redirect('/');
}

export default async function AdminPage() {
  await exigirSaasAdmin();
  const tenants = await listarResumoAdmin();
  const totalAgendamentos = tenants.reduce((acc, t) => acc + t.agendamentos, 0);

  return (
    <main style={{ maxWidth: 880, margin: '0 auto', padding: '48px 24px' }}>
      <h1 style={{ fontFamily: 'var(--bp-font-serif)', color: 'var(--bp-texto-forte)' }}>
        Admin do SaaS
      </h1>
      <p style={{ color: 'var(--bp-texto-fraco)' }}>
        Visão superadmin (BYPASSRLS) — {tenants.length} tenant(s), {totalAgendamentos}{' '}
        agendamento(s) no total.
      </p>

      <table
        style={{
          width: '100%',
          borderCollapse: 'collapse',
          marginTop: 24,
          background: 'var(--bp-fundo-card)',
          border: '1px solid var(--bp-borda)',
        }}
      >
        <thead>
          <tr style={{ textAlign: 'left', color: 'var(--bp-texto-fraco)' }}>
            <th style={{ padding: 12 }}>Tenant</th>
            <th style={{ padding: 12 }}>Slug</th>
            <th style={{ padding: 12 }}>Agendamentos</th>
            <th style={{ padding: 12 }}>Serviços</th>
            <th style={{ padding: 12 }}>Usuários</th>
            <th style={{ padding: 12 }}>Comentários</th>
          </tr>
        </thead>
        <tbody>
          {tenants.map((t) => (
            <tr key={t.slug} style={{ borderTop: '1px solid var(--bp-borda)' }}>
              <td style={{ padding: 12 }}>{t.nome}</td>
              <td style={{ padding: 12 }}>
                <a href={`/?tenant=${t.slug}`} style={{ color: 'var(--bp-dourado)' }}>
                  {t.slug}
                </a>
              </td>
              <td style={{ padding: 12 }}>{t.agendamentos}</td>
              <td style={{ padding: 12 }}>{t.servicos}</td>
              <td style={{ padding: 12 }}>{t.usuarios}</td>
              <td style={{ padding: 12 }}>{t.comentarios}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </main>
  );
}
