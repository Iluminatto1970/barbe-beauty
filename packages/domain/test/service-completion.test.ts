import { describe, expect, it } from 'vitest';
import { calcularComissao, ComissaoInvalidaError } from '../src/commission.js';
import { finalizarServico, somarDias } from '../src/service-completion.js';

// Dados reais do seed: Corte R$ 35,00, comissão 10 (%), dias_retorno 15;
// config.quantidade_cartoes default = 10 (sistema/conexao.php).
const corte = { nome: 'Corte', valor: 35, comissao: 10, diasRetorno: 15 };

function baseInput(overrides: Partial<Parameters<typeof finalizarServico>[0]> = {}) {
  return {
    servico: corte,
    tipoComissao: 'Porcentagem' as const,
    quantidadeCartoes: 10,
    cartoesAtuais: 0,
    dataAtual: '2026-08-10',
    dataPgto: '2026-08-10',
    valorRecebido: 35,
    funcionarioId: '11',
    clienteId: '1',
    agendamentoId: '7',
    ...overrides,
  };
}

describe('finalizar serviço', () => {
  it('receber pago na hora: comissão nasce pendente (pago=false) e com descrição padrão', () => {
    const r = finalizarServico(baseInput({ dataPgto: '2026-08-10' }));
    expect(r.comissao).not.toBeNull();
    expect(r.comissao!.pago).toBe(false);
    expect(r.comissao!.descricao).toBe('Comissão - Corte');
    expect(r.comissao!.tipo).toBe('Comissão');
  });

  it('INVARIANTE: receber pendente NÃO gera comissão aqui — ela sai na baixa (finance.ts)', () => {
    const r = finalizarServico(baseInput({ dataPgto: '2026-08-15' }));
    expect(r.receber.pago).toBe(false);
    expect(r.comissao).toBeNull();
  });

  it('valor da comissão é consistente com calcularComissao (invariante interno)', () => {
    const r = finalizarServico(baseInput({ dataPgto: '2026-08-10' }));
    expect(r.comissao!.valor).toBe(calcularComissao(corte, 'Porcentagem'));
    expect(r.comissao!.valor).toBe(3.5);
  });

  it('receber pago quando dataPgto <= dataAtual', () => {
    const r = finalizarServico(baseInput({ dataPgto: '2026-08-10' }));
    expect(r.receber.pago).toBe(true);
    expect(r.receber.valor).toBe(35);
    expect(r.receber.tipo).toBe('Serviço');
  });

  it('receber pendente quando dataPgto no futuro', () => {
    const r = finalizarServico(baseInput({ dataPgto: '2026-08-15' }));
    expect(r.receber.pago).toBe(false);
  });

  it('cartão fidelidade soma 1 enquanto não atingir a quantidade', () => {
    expect(finalizarServico(baseInput({ cartoesAtuais: 3 })).cartoes).toBe(4);
  });

  it('cartão cheio reinicia para 0 (regra canônica da agenda)', () => {
    expect(finalizarServico(baseInput({ cartoesAtuais: 9 })).cartoes).toBe(10);
    expect(finalizarServico(baseInput({ cartoesAtuais: 10 })).cartoes).toBe(0);
    expect(finalizarServico(baseInput({ cartoesAtuais: 12 })).cartoes).toBe(0);
  });

  it('data de retorno = dataAtual + diasRetorno', () => {
    expect(finalizarServico(baseInput()).dataRetorno).toBe('2026-08-25');
    expect(somarDias('2026-08-10', 15)).toBe('2026-08-25');
    expect(somarDias('2026-12-28', 7)).toBe('2027-01-04');
  });

  it('marca o agendamento como Concluído', () => {
    expect(finalizarServico(baseInput()).agendamentoStatus).toBe('Concluído');
  });

  it('rejeita configuração de fidelidade inválida', () => {
    expect(() => finalizarServico(baseInput({ cartoesAtuais: -1 }))).toThrow(ComissaoInvalidaError);
    expect(() => finalizarServico(baseInput({ quantidadeCartoes: 0 }))).toThrow(
      ComissaoInvalidaError,
    );
  });
});
