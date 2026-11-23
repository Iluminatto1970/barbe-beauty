/**
 * Schema Postgres multi-tenant — mapeamento das 21 tabelas do MySQL
 * (`sql/barbearia.sql`) para o PostgreSQL (Neon em produção).
 *
 * Decisões de mapeamento:
 *  - `id` int AUTO_INCREMENT → `integer` identity (`generatedByDefaultAsIdentity`,
 *    permite inserir ids explícitos na migração MySQL → PG);
 *  - `varchar(n)` → `text`, `decimal(8,2)` → `numeric(8,2)`, `time` → `time`,
 *    flags 'Sim'/'Não' (varchar(5)) mantidas como `text` (migração fiel);
 *    candidatas a `boolean` numa refatoração futura;
 *  - TODA tabela de negócio recebe `tenant_id` (uuid, FK p/ tenants) + RLS
 *    (`rowLevelSecurityEnabled` + policy `tenant_isolation`);
 *  - o MySQL não tinha FKs — adicionamos referências reais no PG;
 *  - `clientes.data_nasc` e `pagar/receber.data_pgto` viraram NULLABLE: o MySQL
 *    aceitava '0000-00-00', que o Postgres rejeita (migração mapeia p/ NULL).
 */
import {
  date,
  index,
  integer,
  numeric,
  pgPolicy,
  pgTable,
  text,
  time,
  timestamp,
  uniqueIndex,
  uuid,
  type PgTableExtraConfigValue,
} from 'drizzle-orm/pg-core';
import { sql } from 'drizzle-orm';

/** Colunas comuns. */
const id = () => integer().generatedByDefaultAsIdentity().primaryKey();
const tenantId = () => uuid('tenant_id').notNull().references(() => tenants.id);

/**
 * Policy RLS padrão: todas as operações ficam restritas ao tenant da sessão.
 * O `ENABLE ROW LEVEL SECURITY` é aplicado por `sql/02-enable-rls.sql`
 * (o drizzle-kit desta versão não gera esse ALTER automaticamente).
 */
function isolacaoPorTenant(): PgTableExtraConfigValue[] {
  return [
    pgPolicy('tenant_isolation', {
      as: 'permissive',
      for: 'all',
      to: 'authenticated',
      using: sql`tenant_id = current_setting('app.tenant_id', true)::uuid`,
      withCheck: sql`tenant_id = current_setting('app.tenant_id', true)::uuid`,
    }),
  ];
}

// ============================================================
// Plataforma (sem tenant — registra as barbearias)
// ============================================================

export const tenants = pgTable('tenants', {
  id: uuid('id').primaryKey().defaultRandom(),
  slug: text('slug').notNull().unique(),
  nome: text('nome').notNull(),
  /** Id da organização no Clerk (B2B: org = tenant) — liga o login à barbearia. */
  orgId: text('org_id').unique(),
  customDomain: text('custom_domain'),
  createdAt: timestamp('created_at').defaultNow().notNull(),
});

export type Tenant = typeof tenants.$inferSelect;
export type NewTenant = typeof tenants.$inferInsert;

// ============================================================
// Catálogo e organização
// ============================================================

export const grupoAcessos = pgTable(
  'grupo_acessos',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
  },
  (t) => [index('grupo_acessos_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const acessos = pgTable(
  'acessos',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
    chave: text('chave').notNull(),
    grupo: integer('grupo').notNull().references(() => grupoAcessos.id),
  },
  (t) => [index('acessos_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const cargos = pgTable(
  'cargos',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
  },
  (t) => [index('cargos_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const catServicos = pgTable(
  'cat_servicos',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
  },
  (t) => [index('cat_servicos_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const catProdutos = pgTable(
  'cat_produtos',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
  },
  (t) => [index('cat_produtos_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

// ============================================================
// Usuários e permissões
// ============================================================

export const usuarios = pgTable(
  'usuarios',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
    email: text('email').notNull(),
    cpf: text('cpf'),
    senha: text('senha').notNull(),
    senhaCrip: text('senha_crip').notNull(),
    nivel: text('nivel').notNull(),
    data: date('data').notNull(),
    ativo: text('ativo').notNull(),
    telefone: text('telefone'),
    endereco: text('endereco'),
    foto: text('foto'),
    atendimento: text('atendimento').notNull(),
    tipoChave: text('tipo_chave'),
    chavePix: text('chave_pix'),
  },
  (t) => [
    index('usuarios_tenant_idx').on(t.tenantId),
    uniqueIndex('usuarios_email_tenant').on(t.tenantId, t.email),
    ...isolacaoPorTenant(),
  ],
);

export const usuariosPermissoes = pgTable(
  'usuarios_permissoes',
  {
    id: id(),
    tenantId: tenantId(),
    usuario: integer('usuario').notNull().references(() => usuarios.id),
    permissao: integer('permissao').notNull().references(() => acessos.id),
  },
  (t) => [index('usuarios_permissoes_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

// ============================================================
// Clientes e agenda
// ============================================================

export const clientes = pgTable(
  'clientes',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
    telefone: text('telefone'),
    endereco: text('endereco'),
    dataNasc: date('data_nasc'), // NULLABLE: MySQL aceitava '0000-00-00'
    dataCad: date('data_cad').notNull(),
    cartoes: integer('cartoes').notNull(),
    dataRetorno: date('data_retorno'),
    ultimoServico: integer('ultimo_servico').notNull(),
    alertado: text('alertado'),
  },
  (t) => [index('clientes_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const servicos = pgTable(
  'servicos',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
    categoria: integer('categoria').notNull().references(() => catServicos.id),
    valor: numeric('valor', { precision: 8, scale: 2 }).notNull(),
    foto: text('foto'),
    diasRetorno: integer('dias_retorno').notNull(),
    ativo: text('ativo').notNull(),
    comissao: numeric('comissao', { precision: 8, scale: 2 }).notNull(),
  },
  (t) => [index('servicos_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const dias = pgTable(
  'dias',
  {
    id: id(),
    tenantId: tenantId(),
    dia: text('dia').notNull(),
    funcionario: integer('funcionario').notNull().references(() => usuarios.id),
  },
  (t) => [index('dias_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const horarios = pgTable(
  'horarios',
  {
    id: id(),
    tenantId: tenantId(),
    horario: time('horario').notNull(),
    funcionario: integer('funcionario').notNull().references(() => usuarios.id),
  },
  (t) => [index('horarios_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const agendamentos = pgTable(
  'agendamentos',
  {
    id: id(),
    tenantId: tenantId(),
    funcionario: integer('funcionario').notNull().references(() => usuarios.id),
    cliente: integer('cliente').notNull().references(() => clientes.id),
    data: date('data').notNull(),
    hora: time('hora').notNull(),
    obs: text('obs'),
    dataLanc: date('data_lanc').notNull(),
    usuario: integer('usuario').notNull().references(() => usuarios.id),
    status: text('status').notNull(),
    servico: integer('servico').notNull().references(() => servicos.id),
  },
  (t) => [
    index('agendamentos_tenant_idx').on(t.tenantId),
    // Invariante de domínio no banco: um slot (data+hora+funcionario) por tenant
    uniqueIndex('agendamentos_slot_unico').on(t.tenantId, t.funcionario, t.data, t.hora),
    ...isolacaoPorTenant(),
  ],
);

export const comentarios = pgTable(
  'comentarios',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
    texto: text('texto').notNull(),
    foto: text('foto'),
    ativo: text('ativo').notNull(),
  },
  (t) => [index('comentarios_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

// ============================================================
// Produtos, estoque e fornecedores
// ============================================================

export const produtos = pgTable(
  'produtos',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
    descricao: text('descricao'),
    categoria: integer('categoria').notNull().references(() => catProdutos.id),
    valorCompra: numeric('valor_compra', { precision: 8, scale: 2 }).notNull(),
    valorVenda: numeric('valor_venda', { precision: 8, scale: 2 }).notNull(),
    estoque: integer('estoque').notNull(),
    foto: text('foto'),
    nivelEstoque: integer('nivel_estoque').notNull(),
  },
  (t) => [index('produtos_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const entradas = pgTable(
  'entradas',
  {
    id: id(),
    tenantId: tenantId(),
    produto: integer('produto').notNull().references(() => produtos.id),
    quantidade: integer('quantidade').notNull(),
    motivo: text('motivo').notNull(),
    usuario: integer('usuario').notNull().references(() => usuarios.id),
    data: date('data').notNull(),
  },
  (t) => [index('entradas_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const saidas = pgTable(
  'saidas',
  {
    id: id(),
    tenantId: tenantId(),
    produto: integer('produto').notNull().references(() => produtos.id),
    quantidade: integer('quantidade').notNull(),
    motivo: text('motivo').notNull(),
    usuario: integer('usuario').notNull().references(() => usuarios.id),
    data: date('data').notNull(),
  },
  (t) => [index('saidas_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const fornecedores = pgTable(
  'fornecedores',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
    telefone: text('telefone'),
    endereco: text('endereco'),
    dataCad: date('data_cad').notNull(),
    tipoChave: text('tipo_chave'),
    chavePix: text('chave_pix'),
  },
  (t) => [index('fornecedores_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

// ============================================================
// Financeiro
// ============================================================

export const receber = pgTable(
  'receber',
  {
    id: id(),
    tenantId: tenantId(),
    descricao: text('descricao'),
    tipo: text('tipo'),
    valor: numeric('valor', { precision: 8, scale: 2 }).notNull(),
    dataLanc: date('data_lanc').notNull(),
    dataVenc: date('data_venc').notNull(),
    dataPgto: date('data_pgto'), // NULLABLE: '0000-00-00' no MySQL
    usuarioLanc: integer('usuario_lanc').notNull().references(() => usuarios.id),
    usuarioBaixa: integer('usuario_baixa').notNull().references(() => usuarios.id),
    foto: text('foto'),
    pessoa: integer('pessoa').notNull().references(() => clientes.id),
    pago: text('pago').notNull(),
    produto: integer('produto').notNull().references(() => produtos.id),
    quantidade: integer('quantidade').notNull(),
    servico: integer('servico').notNull().references(() => servicos.id),
    funcionario: integer('funcionario').notNull().references(() => usuarios.id),
  },
  (t) => [index('receber_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const pagar = pgTable(
  'pagar',
  {
    id: id(),
    tenantId: tenantId(),
    descricao: text('descricao'),
    tipo: text('tipo'),
    valor: numeric('valor', { precision: 8, scale: 2 }).notNull(),
    dataLanc: date('data_lanc').notNull(),
    dataVenc: date('data_venc').notNull(),
    dataPgto: date('data_pgto'), // NULLABLE: '0000-00-00' no MySQL
    usuarioLanc: integer('usuario_lanc').notNull().references(() => usuarios.id),
    usuarioBaixa: integer('usuario_baixa').notNull().references(() => usuarios.id),
    foto: text('foto'),
    pessoa: integer('pessoa').notNull().references(() => clientes.id),
    pago: text('pago').notNull(),
    produto: integer('produto').notNull().references(() => produtos.id),
    quantidade: integer('quantidade').notNull(),
    funcionario: integer('funcionario').notNull().references(() => usuarios.id),
    servico: integer('servico').notNull().references(() => servicos.id),
    cliente: integer('cliente').notNull().references(() => clientes.id),
  },
  (t) => [index('pagar_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

// ============================================================
// Site público e configuração
// ============================================================

export const textosIndex = pgTable(
  'textos_index',
  {
    id: id(),
    tenantId: tenantId(),
    titulo: text('titulo').notNull(),
    descricao: text('descricao').notNull(),
  },
  (t) => [index('textos_index_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);

export const config = pgTable(
  'config',
  {
    id: id(),
    tenantId: tenantId(),
    nome: text('nome').notNull(),
    email: text('email').notNull(),
    telefoneFixo: text('telefone_fixo'),
    telefoneWhatsapp: text('telefone_whatsapp').notNull(),
    endereco: text('endereco'),
    logo: text('logo'),
    icone: text('icone'),
    logoRel: text('logo_rel'),
    tipoRel: text('tipo_rel'),
    instagram: text('instagram'),
    tipoComissao: text('tipo_comissao').notNull(),
    textoRodape: text('texto_rodape'),
    imgBannerIndex: text('img_banner_index'),
    textoSobre: text('texto_sobre'),
    imagemSobre: text('imagem_sobre'),
    iconeSite: text('icone_site').notNull(),
    mapa: text('mapa'),
    textoFidelidade: text('texto_fidelidade'),
    quantidadeCartoes: integer('quantidade_cartoes').notNull(),
    textoAgendamento: text('texto_agendamento'),
    msgAgendamento: text('msg_agendamento'),
  },
  (t) => [index('config_tenant_idx').on(t.tenantId), ...isolacaoPorTenant()],
);
