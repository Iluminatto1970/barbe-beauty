/**
 * Planos e limites do freemium — decisão do SAAS_PLANO.md (tabela de planos).
 *
 * A fonte da verdade dos limites mora AQUI (domain), não na UI nem na API:
 * - a API/UI só consultam `limitesDe()`/`funcionalidadeHabilitada()`;
 * - o enforcement acontece **no momento da ação** (ex.: ao confirmar um
 *   agendamento, comparar o uso do mês contra o limite), não em job de
 *   agregação — assim o usuário do plano grátis não contorna o limite só
 *   porque a contagem roda à meia-noite.
 *
 * Convenções:
 * - `null` = ilimitado;
 * - valores negativos/uso inválido lançam erro (o legado não tinha planos);
 * - precificação (R$) NÃO mora aqui — é responsabilidade do Stripe/Fase 6.
 */

export type Plano = 'Gratis' | 'Pro' | 'Premium';

export interface LimitesPlano {
  /** null = ilimitado. */
  agendamentosPorMes: number | null;
  /** null = ilimitado. */
  funcionarios: number | null;
  financeiro: boolean; // contas a pagar/receber + comissões
  estoque: boolean; // produtos/entradas/saídas
  dominioProprio: boolean; // custom_domain + verificação
}

export const PLANOS: Record<Plano, LimitesPlano> = {
  Gratis: {
    agendamentosPorMes: 50,
    funcionarios: 2,
    financeiro: false,
    estoque: false,
    dominioProprio: false,
  },
  Pro: {
    agendamentosPorMes: 500,
    funcionarios: 5,
    financeiro: true,
    estoque: false,
    dominioProprio: false,
  },
  Premium: {
    agendamentosPorMes: null,
    funcionarios: null,
    financeiro: true,
    estoque: true,
    dominioProprio: true,
  },
};

export class PlanoInvalidoError extends Error {
  constructor(mensagem: string) {
    super(mensagem);
    this.name = 'PlanoInvalidoError';
  }
}

export function limitesDe(plano: Plano): LimitesPlano {
  const limites = PLANOS[plano];
  if (!limites) {
    throw new PlanoInvalidoError(`Plano desconhecido: ${plano}`);
  }
  return limites;
}

/** Recurso binário (financeiro/estoque/domínio próprio) liberado no plano? */
export function funcionalidadeHabilitada(
  plano: Plano,
  recurso: 'financeiro' | 'estoque' | 'dominioProprio',
): boolean {
  return limitesDe(plano)[recurso];
}

export interface UsoRecursos {
  /** Agendamentos do mês corrente (contados no momento da ação). */
  agendamentosMes?: number;
  /** Funcionários ativos do tenant. */
  funcionarios?: number;
}

export interface ViolacaoLimite {
  recurso: 'agendamentosMes' | 'funcionarios';
  limite: number;
  uso: number;
  mensagem: string;
}

export interface VerificacaoLimites {
  /** true se pelo menos um limite foi excedido. */
  excede: boolean;
  violacoes: ViolacaoLimite[];
}

function validarUso(recurso: string, uso: number): void {
  if (!Number.isInteger(uso) || uso < 0) {
    throw new PlanoInvalidoError(`Uso inválido para "${recurso}": ${uso}`);
  }
}

/**
 * Verifica se o uso atual excede os limites do plano. Só os campos presentes em
 * `uso` são verificados — a chamada informa o que mediu na ação.
 */
export function verificarLimites(plano: Plano, uso: UsoRecursos = {}): VerificacaoLimites {
  const limites = limitesDe(plano);
  const violacoes: ViolacaoLimite[] = [];

  if (uso.agendamentosMes !== undefined) {
    validarUso('agendamentosMes', uso.agendamentosMes);
    const limite = limites.agendamentosPorMes;
    if (limite !== null && uso.agendamentosMes > limite) {
      violacoes.push({
        recurso: 'agendamentosMes',
        limite,
        uso: uso.agendamentosMes,
        mensagem: `Limite de ${limite} agendamentos/mês do plano ${plano} excedido (uso: ${uso.agendamentosMes}).`,
      });
    }
  }

  if (uso.funcionarios !== undefined) {
    validarUso('funcionarios', uso.funcionarios);
    const limite = limites.funcionarios;
    if (limite !== null && uso.funcionarios > limite) {
      violacoes.push({
        recurso: 'funcionarios',
        limite,
        uso: uso.funcionarios,
        mensagem: `Limite de ${limite} funcionários do plano ${plano} excedido (uso: ${uso.funcionarios}).`,
      });
    }
  }

  return { excede: violacoes.length > 0, violacoes };
}

/** Atalho de uma linha para os pontos de enforcement. */
export function excedeLimite(plano: Plano, uso: UsoRecursos): boolean {
  return verificarLimites(plano, uso).excede;
}
