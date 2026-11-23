/**
 * Seed + DDL do ambiente dev (pg-mem). Em produção (`DATABASE_URL` definida) o
 * schema vem da migration do `@barbearia/db` e os dados da migração/importador
 * — este arquivo não faz nada lá (exceto o onboarding, ver fim do arquivo).
 *
 * Fontes dos dados:
 *  - **Catálogo real do legado** (`sql/barbearia.sql` via `@barbearia/import`):
 *    servicos, produtos, comentarios, textos_index (hero), usuários e escalas;
 *  - **Identidade/contato do site** (nome, endereço, WhatsApp, horário…):
 *    os valores validados no spike (mesmos exibidos pelo mock) — gravados no
 *    banco; os barbeiros 11/12/13 e o tenant vintage vêm do mock.
 */
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { sql } from 'drizzle-orm';
import { importarDump, parseDump } from '@barbearia/import';
import { PLANOS } from '@barbearia/domain';
import { db, isDevMem } from './db';

const TENANT_ELITE = 'barbearia-elite';
const TENANT_VINTAGE = 'barbearia-vintage';

/** Lê o dump do legado (raiz do monorepo). Só roda no dev (pg-mem). */
function lerDumpSql(): string {
  return readFileSync(join(process.cwd(), '..', '..', 'sql', 'barbearia.sql'), 'utf8');
}

// ============================================================
// DDL do dev (nomes de tabela/coluna iguais ao schema de @barbearia/db;
// tipos simplificados — o pg-mem não precisa de uuid/identity).
// Colunas extras (tagline/horario/hero_*): só no dev; em produção o site
// adapta o texto para config/textos_index reais.
// ============================================================

const DDL = [
  `CREATE TABLE IF NOT EXISTS tenants (
    id text PRIMARY KEY, slug text NOT NULL UNIQUE, nome text NOT NULL,
    org_id text UNIQUE, custom_domain text, created_at timestamptz DEFAULT now())`,
  `CREATE TABLE IF NOT EXISTS config (
    id text, tenant_id text NOT NULL, PRIMARY KEY (tenant_id, id),
    nome text, email text, telefone_fixo text, telefone_whatsapp text, endereco text,
    logo text, icone text, logo_rel text, tipo_rel text, instagram text, tipo_comissao text,
    texto_rodape text, img_banner_index text, texto_sobre text, imagem_sobre text, icone_site text,
    mapa text, texto_fidelidade text, quantidade_cartoes integer, texto_agendamento text, msg_agendamento text,
    tagline text, horario text)`,
  `CREATE TABLE IF NOT EXISTS textos_index (
    id text, tenant_id text NOT NULL, titulo text, descricao text, PRIMARY KEY (tenant_id, id))`,
  `CREATE TABLE IF NOT EXISTS servicos (
    id text, tenant_id text NOT NULL, nome text, categoria integer,
    valor numeric(8,2), foto text, dias_retorno integer, ativo text, comissao numeric(8,2),
    PRIMARY KEY (tenant_id, id))`,
  `CREATE TABLE IF NOT EXISTS produtos (
    id text, tenant_id text NOT NULL, nome text, descricao text, categoria integer,
    valor_compra numeric(8,2), valor_venda numeric(8,2), estoque integer, foto text, nivel_estoque integer,
    PRIMARY KEY (tenant_id, id))`,
  `CREATE TABLE IF NOT EXISTS usuarios (
    id text, tenant_id text NOT NULL, nome text, email text, cpf text,
    senha text, senha_crip text, nivel text, data text, ativo text, telefone text,
    endereco text, foto text, atendimento text, tipo_chave text, chave_pix text,
    PRIMARY KEY (tenant_id, id))`,
  `CREATE TABLE IF NOT EXISTS dias (
    id text, tenant_id text NOT NULL, dia text, funcionario text, PRIMARY KEY (tenant_id, id))`,
  `CREATE TABLE IF NOT EXISTS horarios (
    id text, tenant_id text NOT NULL, horario text, funcionario text, PRIMARY KEY (tenant_id, id))`,
  `CREATE TABLE IF NOT EXISTS clientes (
    id text, tenant_id text NOT NULL, nome text, telefone text, endereco text,
    data_nasc text, data_cad text, cartoes integer, data_retorno text, ultimo_servico integer, alertado text,
    PRIMARY KEY (tenant_id, id))`,
  `CREATE TABLE IF NOT EXISTS agendamentos (
    id text, tenant_id text NOT NULL, funcionario text, cliente text,
    data text, hora text, obs text, data_lanc text, usuario text, status text, servico text,
    PRIMARY KEY (tenant_id, id), UNIQUE (tenant_id, funcionario, data, hora))`,
  `CREATE TABLE IF NOT EXISTS comentarios (
    id text, tenant_id text NOT NULL, nome text, texto text, foto text, ativo text,
    PRIMARY KEY (tenant_id, id))`,
] as const;

// ============================================================
// Identidade/contato do site (valores validados no spike) e barbeiros
// ============================================================

const IDENTIDADE_ELITE = {
  nome: 'Barbearia Elite',
  email: 'contato@barbeariaelite.com',
  telefone_fixo: '(11) 3333-4444',
  telefone_whatsapp: '5511999998888',
  endereco: 'Rua Augusta, 1500 - Consolação - São Paulo/SP',
  instagram: 'barbeariaelite',
  tagline: 'Tradição e qualidade em cortes masculinos desde 2020',
  horario: 'Seg-Sex: 8h às 18h | Sáb: 8h às 16h',
  texto_rodape:
    'Barbearia Elite - Tradição e qualidade em cortes masculinos desde 2020. Agende seu horário e venha conhecer nossos serviços premium!',
  texto_sobre:
    'A Barbearia Elite é referência em cortes masculinos na região central de São Paulo. Agende seu horário online.',
  tipo_comissao: 'R$',
  quantidade_cartoes: 10,
};

const IDENTIDADE_VINTAGE = {
  nome: 'Barbearia Vintage',
  email: 'contato@barbeariavintage.com.br',
  telefone_fixo: '(11) 4444-5555',
  telefone_whatsapp: '5511988887777',
  endereco: 'Rua dos Pinheiros, 880 - Pinheiros - São Paulo/SP',
  instagram: 'barbeariavintage',
  tagline: 'Cortes clássicos com estilo atemporal',
  horario: 'Ter-Sáb: 9h às 20h',
  texto_rodape:
    'Barbearia Vintage - cortes clássicos, barba na navalha e atendimento de respeito.',
  texto_sobre:
    'Barbearia Vintage em Pinheiros: cortes clássicos, barba na navalha e atendimento de respeito.',
  tipo_comissao: 'R$',
  quantidade_cartoes: 10,
};

type EscalaSeed = { diasTrabalho: string[]; horarios: string[] };
type BarbeiroSeed = { id: string; nome: string; escala: EscalaSeed };

const BARBEIROS_ELITE: BarbeiroSeed[] = [
  {
    id: '11',
    nome: 'Felipe',
    escala: {
      diasTrabalho: [
        'Segunda-Feira',
        'Terça-Feira',
        'Quarta-Feira',
        'Quinta-Feira',
        'Sexta-Feira',
        'Sábado',
      ],
      horarios: ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '14:30', '15:00', '15:30', '16:00'],
    },
  },
  {
    id: '12',
    nome: 'Rafael',
    escala: {
      diasTrabalho: ['Terça-Feira', 'Quarta-Feira', 'Quinta-Feira', 'Sexta-Feira', 'Sábado'],
      horarios: ['09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '15:00', '16:00'],
    },
  },
  {
    id: '13',
    nome: 'Carlos',
    escala: {
      diasTrabalho: [
        'Segunda-Feira',
        'Terça-Feira',
        'Quarta-Feira',
        'Quinta-Feira',
        'Sexta-Feira',
      ],
      horarios: ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'],
    },
  },
];

const BARBEIROS_VINTAGE: BarbeiroSeed[] = [
  {
    id: 'v1',
    nome: 'Diego',
    escala: {
      diasTrabalho: ['Terça-Feira', 'Quarta-Feira', 'Quinta-Feira', 'Sexta-Feira', 'Sábado'],
      horarios: ['10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'],
    },
  },
  {
    id: 'v2',
    nome: 'André',
    escala: {
      diasTrabalho: ['Segunda-Feira', 'Quarta-Feira', 'Sexta-Feira', 'Sábado'],
      horarios: ['09:00', '10:00', '11:00', '15:00', '16:00'],
    },
  },
];

const SERVICOS_VINTAGE = [
  { id: '1', nome: 'Corte Clássico', valor: 40.0, foto: '/images/servico-corte.png', comissao: 10, dias_retorno: 15 },
  { id: '2', nome: 'Barba na Navalha', valor: 35.0, foto: '/images/servico-barba.png', comissao: 10, dias_retorno: 7 },
  { id: '3', nome: 'Corte + Barba', valor: 65.0, foto: '/images/servico-corte-barba.png', comissao: 15, dias_retorno: 15 },
];

const PRODUTOS_VINTAGE = [
  { id: '1', nome: 'Pomada Clássica', descricao: 'Fixação forte para o visual retrô.', valor: 25.0, foto: '/images/produto-pomada.png' },
  { id: '2', nome: 'Óleo Pós Barba', descricao: 'Óleo hidratante com aroma de cedro.', valor: 18.0, foto: '/images/produto-oleo.png' },
];

// ============================================================
// Helpers
// ============================================================

/** Escapa um valor para SQL (dados controlados do seed — nunca entrada do usuário). */
function sqlVal(v: unknown): string {
  if (v === null || v === undefined) return 'NULL';
  const s = String(v);
  return `'${s.replace(/'/g, "''")}'`;
}

async function insertLinhas(tabela: string, linhas: Record<string, unknown>[]): Promise<void> {
  const colunas = Object.keys(linhas[0] ?? {});
  if (colunas.length === 0) return;
  const colSql = colunas.map((c) => `"${c}"`).join(', ');
  for (const linha of linhas) {
    const vals = colunas.map((c) => sqlVal(linha[c])).join(', ');
    await db.execute(sql.raw(`INSERT INTO "${tabela}" (${colSql}) VALUES (${vals})`));
  }
}

/** Próxima ocorrência de um dia da semana (1 = segunda ... 6 = sábado), sempre no futuro. */
function proximaDataDiaSemana(diaSemana: number): string {
  const hoje = new Date();
  const diff = ((diaSemana - hoje.getDay() + 7) % 7) || 7;
  const alvo = new Date(hoje);
  alvo.setDate(hoje.getDate() + diff);
  return alvo.toISOString().slice(0, 10);
}

function agendamentosDemo(barbeiros: BarbeiroSeed[]): Record<string, unknown>[] {
  const hoje = new Date().toISOString().slice(0, 10);
  const slots = [
    { dia: 1, hora: '10:00', barbeiro: barbeiros[0]! },
    { dia: 1, hora: '09:00', barbeiro: barbeiros[2] ?? barbeiros[1]! },
    { dia: 3, hora: '14:00', barbeiro: barbeiros[0]! },
    { dia: 2, hora: '09:00', barbeiro: barbeiros[1]! },
  ];
  return slots.map((s, i) => ({
    id: `demo-${i + 1}`,
    tenant_id: '', // preenchido pelo chamador
    funcionario: s.barbeiro.id,
    cliente: '1',
    data: proximaDataDiaSemana(s.dia),
    hora: s.hora,
    obs: null,
    data_lanc: hoje,
    usuario: '6',
    status: 'Agendado',
    servico: '1',
  }));
}

// ============================================================
// Seed principal
// ============================================================

async function sembrarTenant(
  tenantId: string,
  identidade: typeof IDENTIDADE_ELITE,
  barbeiros: BarbeiroSeed[],
  catalogo: {
    servicos?: Record<string, unknown>[];
    produtos?: Record<string, unknown>[];
    comentarios?: Record<string, unknown>[];
    textosIndex?: Record<string, unknown>[];
    usuariosLegado?: Record<string, unknown>[];
    escalasLegado?: { dias: Record<string, unknown>[]; horarios: Record<string, unknown>[] };
  },
): Promise<void> {
  await insertLinhas('tenants', [
    { id: tenantId, slug: tenantId, nome: identidade.nome, custom_domain: null, created_at: new Date().toISOString() },
  ]);

  await insertLinhas('config', [
    {
      id: `${tenantId}-cfg`,
      tenant_id: tenantId,
      nome: identidade.nome,
      email: identidade.email,
      telefone_fixo: identidade.telefone_fixo,
      telefone_whatsapp: identidade.telefone_whatsapp,
      endereco: identidade.endereco,
      instagram: identidade.instagram,
      tipo_comissao: identidade.tipo_comissao,
      texto_rodape: identidade.texto_rodape,
      texto_sobre: identidade.texto_sobre,
      icone_site: 'favicon.png',
      quantidade_cartoes: identidade.quantidade_cartoes,
      tagline: identidade.tagline,
      horario: identidade.horario,
    },
  ]);

  if (catalogo.servicos) await insertLinhas('servicos', catalogo.servicos);
  if (catalogo.produtos) await insertLinhas('produtos', catalogo.produtos);
  if (catalogo.comentarios) await insertLinhas('comentarios', catalogo.comentarios);
  if (catalogo.textosIndex) await insertLinhas('textos_index', catalogo.textosIndex);

  // usuários legado (do dump) + barbeiros do mock
  const usuarios = [
    ...(catalogo.usuariosLegado ?? []),
    ...barbeiros.map((b, i) => ({
      id: b.id,
      tenant_id: tenantId,
      nome: b.nome,
      email: `barbeiro${i + 1}@${tenantId}.com`,
      cpf: null,
      senha: '',
      senha_crip: '',
      nivel: 'Barbeiro',
      data: new Date().toISOString().slice(0, 10),
      ativo: 'Sim',
      telefone: null,
      endereco: null,
      foto: 'sem-foto.jpg',
      atendimento: 'Sim',
      tipo_chave: null,
      chave_pix: null,
    })),
  ];
  await insertLinhas('usuarios', usuarios);

  // escalas: barbeiros do mock
  let diaId = 1;
  let horId = 1;
  const dias: Record<string, unknown>[] = [];
  const horarios: Record<string, unknown>[] = [];
  for (const b of barbeiros) {
    for (const dia of b.escala.diasTrabalho) {
      dias.push({ id: String(diaId++), tenant_id: tenantId, dia, funcionario: b.id });
    }
    for (const hora of b.escala.horarios) {
      horarios.push({ id: String(horId++), tenant_id: tenantId, horario: hora, funcionario: b.id });
    }
  }
  // + escalas reais do legado (dump) — ids renomeados p/ não colidir com os do mock
  if (catalogo.escalasLegado) {
    dias.push(...catalogo.escalasLegado.dias.map((d) => ({ ...d, id: `l${String(d.id)}` })));
    horarios.push(...catalogo.escalasLegado.horarios.map((h) => ({ ...h, id: `l${String(h.id)}` })));
  }
  await insertLinhas('dias', dias);
  await insertLinhas('horarios', horarios);

  // agendamentos de demonstração (slots ocupados em datas futuras)
  await insertLinhas(
    'agendamentos',
    agendamentosDemo(barbeiros).map((a) => ({ ...a, tenant_id: tenantId })),
  );
}

let seedPromise: Promise<void> | null = null;

export function garantirSeed(): Promise<void> {
  if (!isDevMem) return Promise.resolve();
  seedPromise ??= executarSeed();
  return seedPromise;
}

async function executarSeed(): Promise<void> {
  for (const ddl of DDL) {
    await db.execute(sql.raw(ddl));
  }
  // banco em memória criado antes da coluna org_id (dev server antigo)
  try {
    await db.execute(sql.raw('ALTER TABLE tenants ADD COLUMN IF NOT EXISTS org_id text UNIQUE'));
  } catch {
    // pg-mem de versões antigas pode não suportar o ALTER — segue sem a coluna
  }

  const existentes = await db.execute(sql`SELECT 1 FROM tenants LIMIT 1`);
  if (existentes.rowCount && existentes.rowCount > 0) return;

  // ---- Elite: catálogo e textos reais do legado via @barbearia/import ----
  const dump = importarDump(lerDumpSql(), TENANT_ELITE);

  const stringifyIds = <T extends Record<string, unknown>>(l: T): Record<string, unknown> =>
    Object.fromEntries(
      Object.entries(l).map(([k, v]) =>
        ['id', 'funcionario', 'categoria', 'cliente', 'usuario', 'servico'].includes(k)
          ? [k, v === null ? null : String(v)]
          : [k, v],
      ),
    );

  const servicos = (dump.tabelas.servicos ?? []).map(stringifyIds);
  const produtos = (dump.tabelas.produtos ?? []).map(stringifyIds);
  const comentarios = (dump.tabelas.comentarios ?? []).map(stringifyIds);
  const textosIndex = (dump.tabelas.textos_index ?? []).map(stringifyIds);

  // usuários do dump: só os que são barbeiros de atendimento (Admin fica de fora)
  const usuariosLegado = (dump.tabelas.usuarios ?? [])
    .filter((u) => u.atendimento === 'Sim' && u.nivel !== 'Administrador')
    .map(stringifyIds);

  // escalas reais do dump (para os barbeiros legados; o 11 ganha a escala do mock)
  const escalasLegado = {
    dias: (dump.tabelas.dias ?? [])
      .filter((d) => d.funcionario !== 11)
      .map(stringifyIds),
    horarios: (dump.tabelas.horarios ?? [])
      .filter((h) => h.funcionario !== 11)
      .map(stringifyIds),
  };

  await sembrarTenant(TENANT_ELITE, IDENTIDADE_ELITE, BARBEIROS_ELITE, {
    servicos,
    produtos,
    comentarios,
    textosIndex,
    usuariosLegado,
    escalasLegado,
  });

  // ---- Vintage: identidade + catálogo do mock (dados fictícios do spike) ----
  await sembrarTenant(TENANT_VINTAGE, IDENTIDADE_VINTAGE, BARBEIROS_VINTAGE, {
    textosIndex: [
      {
        id: '1',
        tenant_id: TENANT_VINTAGE,
        titulo: 'Clássico nunca sai de moda',
        descricao:
          'Navalha, tesoura e tradição. Experimente o ritual de barbearia de verdade.',
      },
    ],
    servicos: SERVICOS_VINTAGE.map((s) => ({ ...s, tenant_id: TENANT_VINTAGE, ativo: 'Sim' })),
    produtos: PRODUTOS_VINTAGE.map((p) => ({
      id: p.id,
      nome: p.nome,
      descricao: p.descricao,
      foto: p.foto,
      tenant_id: TENANT_VINTAGE,
      valor_compra: p.valor * 0.5,
      valor_venda: p.valor,
      estoque: 20,
      nivel_estoque: 5,
      categoria: 1,
    })),
  });
}

// ============================================================
// Onboarding (Fase 1): org do Clerk → tenant provisionado no banco
// ============================================================

const SLUGS_RESERVADOS = new Set(['www', 'localhost', 'minhasaas', 'app', 'admin', 'api']);

/** Slug a partir do nome da barbearia (sem acentos, só [a-z0-9-]). */
function slugDe(nome: string): string {
  const base = nome
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // remove acentos
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
  const limpo = base || 'barbearia';
  return SLUGS_RESERVADOS.has(limpo) ? `b-${limpo}` : limpo;
}

/** Identidade de um tenant recém-criado (dados placeholder que o dono edita). */
function identidadeDemo(nome: string, slug: string): typeof IDENTIDADE_ELITE {
  return {
    nome,
    email: `contato@${slug}.com.br`,
    telefone_fixo: '',
    telefone_whatsapp: '5511999990000',
    endereco: 'A definir — complete seus dados no painel',
    instagram: slug,
    tagline: 'Sua barbearia, agora com agendamento online',
    horario: 'Seg-Sex: 9h às 18h | Sáb: 9h às 13h',
    texto_rodape: `${nome} - Agende seu horário online e venha conhecer nossos serviços.`,
    texto_sobre: `${nome} - Agende seu horário online.`,
    tipo_comissao: 'R$',
    quantidade_cartoes: 10,
  };
}

/** Catálogo demo de um tenant recém-criado (preços reais do legado). */
function catalogoDemo(tenantId: string): Record<string, unknown>[] {
  return [
    { id: '1', nome: 'Corte', valor: 35.0, foto: null, comissao: 10, dias_retorno: 15, ativo: 'Sim', categoria: 1, tenant_id: tenantId },
    { id: '2', nome: 'Barba', valor: 30.0, foto: null, comissao: 10, dias_retorno: 7, ativo: 'Sim', categoria: 1, tenant_id: tenantId },
    { id: '3', nome: 'Corte + Barba', valor: 60.0, foto: null, comissao: 15, dias_retorno: 15, ativo: 'Sim', categoria: 1, tenant_id: tenantId },
  ];
}

function produtosDemo(tenantId: string): Record<string, unknown>[] {
  return [
    { id: '1', nome: 'Pomada Gel Modeladora', descricao: 'Fixação média com brilho controlado.', valor_compra: 12.0, valor_venda: 25.0, estoque: 20, nivel_estoque: 5, categoria: 1, foto: null, tenant_id: tenantId },
    { id: '2', nome: 'Loção Pós Barba', descricao: 'Refrescante, com aloe vera.', valor_compra: 10.0, valor_venda: 22.0, estoque: 15, nivel_estoque: 4, categoria: 1, foto: null, tenant_id: tenantId },
    { id: '3', nome: 'Creme Hidratação', descricao: 'Hidratação profunda para cabelos e barba.', valor_compra: 9.0, valor_venda: 20.0, estoque: 12, nivel_estoque: 3, categoria: 1, foto: null, tenant_id: tenantId },
  ];
}

/**
 * Garante que a org do Clerk tenha tenant provisionado; retorna o slug.
 * Idempotente (org_id UNIQUE): concorrência cai no re-read. O limite de
 * funcionários da org é sincronizado com o plano (Grátis = 2, `plans.ts`).
 *
 * ⚠️ Hoje só existe no dev (pg-mem). Em produção (`DATABASE_URL`), o schema
 * real tem a coluna `org_id`, mas o provisionamento deve vir do webhook
 * `organization.created` na API (Fase 7/importador) — lança erro claro aqui.
 */
export async function garantirTenantDaOrg(
  orgId: string,
  /** Injeta o nome da org (testes); sem isso, busca no Clerk (só na 1ª vez). */
  obterNome?: () => Promise<string>,
): Promise<string> {
  await garantirSeed();

  const jaExiste = await db.execute(
    sql.raw(`SELECT slug FROM tenants WHERE org_id = ${sqlVal(orgId)} LIMIT 1`),
  );
  const linha = jaExiste.rows[0] as { slug?: string } | undefined;
  if (linha?.slug) return String(linha.slug);

  if (!isDevMem) {
    throw new Error(
      'Provisionamento de tenant em produção ainda não implementado (webhook organization.created na API).',
    );
  }

  // nome da org vem do Clerk (uma chamada de API, só na primeira vez)
  const nome = obterNome
    ? await obterNome()
    : await (async () => {
        const { clerkClient } = await import('@clerk/nextjs/server');
        const client = await clerkClient();
        const org = await client.organizations.getOrganization({ organizationId: orgId });
        return org.name;
      })();

  // slug único (se o slug base existir, sufixa -2, -3, ...)
  const base = slugDe(nome);
  let slug = base;
  let i = 2;
  for (;;) {
    const ocupado = await db.execute(
      sql.raw(`SELECT 1 FROM tenants WHERE slug = ${sqlVal(slug)} LIMIT 1`),
    );
    if (!(ocupado.rowCount && ocupado.rowCount > 0)) break;
    slug = `${base}-${i++}`;
  }

  // limite Grátis = 2 funcionários (fonte da verdade: plans.ts no domain)
  const barbeiros = BARBEIROS_ELITE.slice(0, PLANOS.Gratis.funcionarios ?? Infinity);
  await sembrarTenant(slug, identidadeDemo(nome, slug), barbeiros, {
    textosIndex: [
      {
        id: '1',
        tenant_id: slug,
        titulo: 'Bem-vindo à sua barbearia',
        descricao: 'Sua barbearia agora tem site próprio e agendamento online.',
      },
    ],
    servicos: catalogoDemo(slug),
    produtos: produtosDemo(slug),
  });

  // grava o vínculo org → tenant (a tabela foi inserida sem org_id)
  await db.execute(
    sql.raw(`UPDATE tenants SET org_id = ${sqlVal(orgId)} WHERE slug = ${sqlVal(slug)}`),
  );

  // sincroniza o limite de membros da org com o plano (falha não derruba o onboarding)
  if (!obterNome) {
    try {
      const { clerkClient } = await import('@clerk/nextjs/server');
      const client = await clerkClient();
      await client.organizations.updateOrganization(orgId, {
        maxAllowedMemberships: PLANOS.Gratis.funcionarios ?? undefined,
      });
    } catch (erro) {
      console.warn('[onboarding] aviso ao sincronizar limite da org:', erro);
    }
  }

  return slug;
}
