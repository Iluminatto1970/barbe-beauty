/**
 * Repositório de leitura do site público por tenant + visão superadmin.
 *
 * Usa SQL cru montado como STRING e embrulhado em `sql.raw(...)`: o driver
 * `pg` em produção e o pg-mem no dev. (Não usamos template `sql` com
 * interpolação: o drizzle node-postgres parametriza `${...}` com prepared
 * statements `getTypeParser`/`rowMode`, que o pg-mem não suporta; e a
 * interpolação de `sql.raw` dentro de template não sobrevive ao bundle do
 * Next.) Os valores são slugs/ids de rotas e dados do seed — nunca input
 * livre — e ainda assim escapados por `esc()`.
 *
 * Isolamento: TODA query de negócio filtra por `tenant_id` (defesa nº 1). Em
 * produção, o RLS do schema (`@barbearia/db`) é a segunda barreira via
 * `set_config('app.tenant_id', ...)`.
 *
 * O `/admin` usa as funções `*Admin` SEM filtro de tenant — em produção isso
 * exige a role `saas_admin` com BYPASSRLS (`packages/db/sql/03-saas-admin.sql`).
 */
import { sql } from 'drizzle-orm';
import { db } from './db';
import { garantirSeed } from './seed';
import type { Agendamento } from '@barbearia/domain';
import type { Funcionario, ProdutoPublico, ServicoPublico, Tenant } from './types';

export const TENANT_PADRAO = 'barbearia-elite';

/** Foto pública no /public do Next — o banco guarda o nome original do upload. */
const FOTO_SERVICO: Record<string, string> = {
  Corte: '/images/servico-corte.png',
  Barba: '/images/servico-barba.png',
  'Corte + Barba': '/images/servico-corte-barba.png',
  'Corte Clássico': '/images/servico-corte.png',
  'Barba na Navalha': '/images/servico-barba.png',
};
const FOTO_PRODUTO: Record<string, string> = {
  'Pomada Gel Modeladora': '/images/produto-pomada.png',
  'Pomada Modeladora': '/images/produto-gel.png',
  'Loção Pós Barba': '/images/produto-oleo.png',
  'Creme Hidratação': '/images/produto-creme.png',
  'Pomada Clássica': '/images/produto-pomada.png',
  'Óleo Pós Barba': '/images/produto-oleo.png',
};

/** Escapa um valor para SQL inline (dados controlados do servidor). */
function esc(v: unknown): string {
  if (v === null || v === undefined) return 'NULL';
  const s = String(v);
  return `'${s.replace(/'/g, "''")}'`;
}

type Linha = Record<string, unknown>;

// ============================================================
// Tenant
// ============================================================

export interface TenantPublicoResumo {
  id: string;
  slug: string;
  nome: string;
}

export async function buscarTenantPorSlug(
  slug: string,
): Promise<TenantPublicoResumo | undefined> {
  await garantirSeed();
  const r = await db.execute(
    sql.raw(`SELECT id, slug, nome FROM tenants WHERE slug = ${esc(slug)} LIMIT 1`),
  );
  const linha = r.rows[0] as Linha | undefined;
  if (!linha) return undefined;
  return { id: String(linha.id), slug: String(linha.slug), nome: String(linha.nome) };
}

/** Lista para o switcher do cabeçalho (slug + nome). */
export async function listarTenantsPublicos(): Promise<TenantPublicoResumo[]> {
  await garantirSeed();
  const r = await db.execute(sql.raw('SELECT id, slug, nome FROM tenants ORDER BY nome'));
  return r.rows.map((linha) => {
    const l = linha as Linha;
    return { id: String(l.id), slug: String(l.slug), nome: String(l.nome) };
  });
}

// ============================================================
// Dados do site (por tenant)
// ============================================================

interface ConfigSiteLinha {
  nome: string | null;
  email: string | null;
  telefone_fixo: string | null;
  telefone_whatsapp: string | null;
  endereco: string | null;
  instagram: string | null;
  tipo_comissao: string | null;
  texto_rodape: string | null;
  texto_sobre: string | null;
  quantidade_cartoes: number | null;
  tagline: string | null;
  horario: string | null;
}

export async function buscarSiteData(tenantId: string): Promise<Tenant | undefined> {
  await garantirSeed();

  const tenant = await buscarTenantPorSlug(tenantId);
  if (!tenant) return undefined;

  const cfgR = await db.execute(sql.raw(`
    SELECT nome, email, telefone_fixo, telefone_whatsapp, endereco, instagram,
           tipo_comissao, texto_rodape, texto_sobre, quantidade_cartoes, tagline, horario
    FROM config WHERE tenant_id = ${esc(tenantId)} LIMIT 1`));
  const cfg = cfgR.rows[0] as ConfigSiteLinha | undefined;

  const heroR = await db.execute(
    sql.raw(
      `SELECT titulo, descricao FROM textos_index WHERE tenant_id = ${esc(tenantId)} ORDER BY id LIMIT 1`,
    ),
  );
  const hero = heroR.rows[0] as Linha | undefined;

  const servR = await db.execute(
    sql.raw(`SELECT nome, valor, foto, ativo FROM servicos WHERE tenant_id = ${esc(tenantId)}`),
  );
  const servicosPublicos: ServicoPublico[] = servR.rows
    .map((linha) => linha as Linha)
    .filter((s) => s.ativo === 'Sim')
    .map((s) => ({
      nome: String(s.nome),
      valor: Number(s.valor),
      foto: FOTO_SERVICO[String(s.nome)] ?? (s.foto ? String(s.foto) : '/images/servico-corte.png'),
    }));

  const prodR = await db.execute(
    sql.raw(
      `SELECT nome, descricao, valor_venda, foto FROM produtos WHERE tenant_id = ${esc(tenantId)}`,
    ),
  );
  const produtosPublicos: ProdutoPublico[] = prodR.rows.map((linha) => {
    const p = linha as Linha;
    return {
      nome: String(p.nome),
      descricao: p.descricao ? String(p.descricao) : '',
      valor: Number(p.valor_venda),
      foto: FOTO_PRODUTO[String(p.nome)] ?? (p.foto ? String(p.foto) : '/images/produto-pomada.png'),
    };
  });

  const funcionarios = await buscarFuncionarios(tenantId);

  return {
    slug: tenant.slug,
    nome: cfg?.nome ?? tenant.nome,
    tagline: cfg?.tagline ?? '',
    descricao: cfg?.texto_sobre ?? cfg?.tagline ?? '',
    heroTitulo: hero ? String(hero.titulo) : tenant.nome,
    heroDescricao: hero ? String(hero.descricao) : '',
    descricaoRodape: cfg?.texto_rodape ?? '',
    endereco: cfg?.endereco ?? '',
    telefone: cfg?.telefone_fixo ?? '',
    whatsapp: cfg?.telefone_whatsapp ?? '',
    email: cfg?.email ?? '',
    instagram: cfg?.instagram ?? '',
    horario: cfg?.horario ?? '',
    servicos: servicosPublicos,
    produtos: produtosPublicos,
    funcionarios,
  };
}

/** Barbeiros = usuários com atendimento 'Sim' e que não são Administrador. */
async function buscarFuncionarios(tenantId: string): Promise<Funcionario[]> {
  const usuR = await db.execute(
    sql.raw(
      `SELECT id, nome, atendimento, nivel FROM usuarios WHERE tenant_id = ${esc(tenantId)}`,
    ),
  );

  const escR = await db.execute(sql.raw(`
    SELECT d.funcionario AS funcionario, d.dia AS dia, h.horario AS horario
    FROM dias d JOIN horarios h ON d.funcionario = h.funcionario
    WHERE d.tenant_id = ${esc(tenantId)} AND h.tenant_id = ${esc(tenantId)}`));

  const diasPorFunc: Record<string, string[]> = {};
  const horariosPorFunc: Record<string, string[]> = {};
  for (const row of escR.rows as Linha[]) {
    const func = String(row.funcionario);
    (diasPorFunc[func] ??= []).push(String(row.dia));
    (horariosPorFunc[func] ??= []).push(String(row.horario));
  }

  return usuR.rows
    .map((linha) => linha as Linha)
    .filter((u) => u.atendimento === 'Sim' && u.nivel !== 'Administrador')
    .map((u) => {
      const id = String(u.id);
      return {
        id,
        nome: String(u.nome),
        escala: {
          funcionarioId: id,
          diasTrabalho: [...new Set(diasPorFunc[id] ?? [])] as Funcionario['escala']['diasTrabalho'],
          horarios: [...new Set(horariosPorFunc[id] ?? [])],
        },
      };
    });
}

/** Agendamentos do tenant (para o wizard: conflito em tempo real). */
export async function buscarAgendamentos(tenantId: string): Promise<Agendamento[]> {
  await garantirSeed();
  const r = await db.execute(
    sql.raw(
      `SELECT id, data, hora, funcionario FROM agendamentos WHERE tenant_id = ${esc(tenantId)}`,
    ),
  );
  return r.rows.map((linha) => {
    const l = linha as Linha;
    return {
      id: String(l.id),
      data: String(l.data),
      hora: String(l.hora),
      funcionarioId: String(l.funcionario),
    };
  });
}

// ============================================================
// Superadmin (sem escopo de tenant — role saas_admin/BYPASSRLS em produção)
// ============================================================

export interface ResumoTenantAdmin {
  slug: string;
  nome: string;
  agendamentos: number;
  servicos: number;
  usuarios: number;
  comentarios: number;
}

export async function listarResumoAdmin(): Promise<ResumoTenantAdmin[]> {
  await garantirSeed();

  // Counts agrupados em queries separadas (o pg-mem não suporta subqueries
  // correlacionadas no SELECT) — montamos o resumo no JS.
  const [t, a, s, u, c] = await Promise.all([
    db.execute(sql.raw('SELECT id, slug, nome FROM tenants ORDER BY nome')),
    db.execute(sql.raw('SELECT tenant_id, count(*) AS n FROM agendamentos GROUP BY tenant_id')),
    db.execute(sql.raw('SELECT tenant_id, count(*) AS n FROM servicos GROUP BY tenant_id')),
    db.execute(sql.raw('SELECT tenant_id, count(*) AS n FROM usuarios GROUP BY tenant_id')),
    db.execute(sql.raw('SELECT tenant_id, count(*) AS n FROM comentarios GROUP BY tenant_id')),
  ]);

  const contagem = (rows: Linha[]): Map<string, number> =>
    new Map(rows.map((l) => [String(l.tenant_id), Number(l.n)]));
  const agendamentos = contagem(a.rows as Linha[]);
  const servicos = contagem(s.rows as Linha[]);
  const usuarios = contagem(u.rows as Linha[]);
  const comentarios = contagem(c.rows as Linha[]);

  return (t.rows as Linha[]).map((l) => ({
    slug: String(l.slug),
    nome: String(l.nome),
    agendamentos: agendamentos.get(String(l.id)) ?? 0,
    servicos: servicos.get(String(l.id)) ?? 0,
    usuarios: usuarios.get(String(l.id)) ?? 0,
    comentarios: comentarios.get(String(l.id)) ?? 0,
  }));
}
