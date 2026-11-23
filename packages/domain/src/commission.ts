/**
 * Comissões — portadas do sistema legado PHP (Barbearia PRO).
 *
 * Fontes:
 *  - `app/sistema/painel/paginas/agenda/inserir-servico.php` e
 *    `servicos_agenda/salvar.php` — fórmula do valor da comissão
 *  - `config.tipo_comissao` — 'Porcentagem' | 'R$' (valores reais do banco)
 *  - `servicos.comissao` — decimal(8,2): percentual (%) ou valor fixo (R$)
 *  - schema `sql/barbearia.sql` + seed: Corte R$ 35,00 com comissão 10,00;
 *    Corte + Barba R$ 60,00 com comissão 15,00
 *
 * Fórmula fiel ao legado:
 *  - Porcentagem: `(comissao * valor) / 100` (o MySQL arredonda na escrita —
 *    aqui arredondamos para 2 casas, half-up);
 *  - R$: `comissao` (valor fixo).
 *
 * Validação adicionada na extração: comissão/valor negativos ou não numéricos
 * lançam erro (o legado não validava; percentual > 100 continua passando como
 * no PHP — sem clamp, para não mudar comportamento).
 */

export type TipoComissao = 'Porcentagem' | 'R$';

export interface Servico {
  nome: string;
  valor: number; // R$
  comissao: number; // % (Porcentagem) ou R$ fixo (R$)
}

export class ComissaoInvalidaError extends Error {
  constructor(mensagem: string) {
    super(mensagem);
    this.name = 'ComissaoInvalidaError';
  }
}

export function arredondar2(n: number): number {
  return Math.round((n + Number.EPSILON) * 100) / 100;
}

function validar(servico: Servico): void {
  if (!Number.isFinite(servico.valor) || servico.valor < 0) {
    throw new ComissaoInvalidaError(`Valor inválido para "${servico.nome}": ${servico.valor}`);
  }
  if (!Number.isFinite(servico.comissao) || servico.comissao < 0) {
    throw new ComissaoInvalidaError(
      `Comissão inválida para "${servico.nome}": ${servico.comissao}`,
    );
  }
}

export function calcularComissao(servico: Servico, tipo: TipoComissao): number {
  validar(servico);

  if (tipo === 'Porcentagem') {
    return arredondar2((servico.comissao * servico.valor) / 100);
  }
  return arredondar2(servico.comissao);
}

export function totalComissoes(servicos: Servico[], tipo: TipoComissao): number {
  return arredondar2(
    servicos.reduce((acc, servico) => acc + calcularComissao(servico, tipo), 0),
  );
}
