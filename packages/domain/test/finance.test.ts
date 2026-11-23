import { describe, expect, it } from 'vitest';
import {
  baixarConta,
  baixarReceber,
  contaPaga,
  ContaInvalidaError,
  criarConta,
  editarConta,
  resumoContas,
  saldo,
} from '../src/finance.js';
import type { Conta } from '../src/finance.js';

// Dados reais do seed (sql/barbearia.sql): Corte R$ 35,00 com comissão 10 (%).
const corte = { nome: 'Corte', valor: 35, comissao: 10 };

function contaServico(overrides: Partial<Conta> = {}): Conta {
  return {
    id: '1',
    descricao: 'Corte',
    tipo: 'Serviço',
    valor: 35,
    dataLanc: '2026-08-10',
    dataVenc: '2026-08-10',
    dataPgto: null,
    pessoa: '5',
    funcionario: '11',
    servico: '1',
    ...overrides,
  };
}

describe('criarConta (INSERT do salvar.php)', () => {
  it('com dataPgto informada nasce PAGA, com usuario_baixa = quem lançou', () => {
    const c = criarConta(
      { descricao: 'Energia', valor: 150.5, dataVenc: '2026-09-05', dataPgto: '2026-08-01' },
      '2',
      '2026-08-01',
    );
    expect(c.tipo).toBe('Conta');
    expect(c.dataLanc).toBe('2026-08-01');
    expect(c.dataPgto).toBe('2026-08-01');
    expect(contaPaga(c)).toBe(true);
    expect(c.usuarioBaixa).toBe('2');
  });

  it('sem dataPgto nasce PENDENTE (dataPgto null, sem usuario_baixa)', () => {
    const c = criarConta(
      { descricao: 'Água', valor: 80, dataVenc: '2026-09-05', dataPgto: null },
      '2',
      '2026-08-01',
    );
    expect(c.dataPgto).toBeNull();
    expect(contaPaga(c)).toBe(false);
    expect(c.usuarioBaixa).toBeUndefined();
  });

  it('valida descrição obrigatória (mensagem fiel ao PHP)', () => {
    expect(() =>
      criarConta({ descricao: '  ', valor: 10, dataVenc: '2026-09-01', dataPgto: null }, '2', '2026-08-01'),
    ).toThrow(/Insira uma descrição/);
  });

  it('INVARIANTE: valor negativo/não numérico é rejeitado (o PHP não validava)', () => {
    expect(() =>
      criarConta({ descricao: 'X', valor: -5, dataVenc: '2026-09-01', dataPgto: null }, '2', '2026-08-01'),
    ).toThrow(ContaInvalidaError);
    expect(() =>
      criarConta({ descricao: 'X', valor: Number.NaN, dataVenc: '2026-09-01', dataPgto: null }, '2', '2026-08-01'),
    ).toThrow(ContaInvalidaError);
  });

  it('arredonda o valor para 2 casas como o decimal(8,2) do MySQL', () => {
    const c = criarConta({ descricao: 'X', valor: 10.005, dataVenc: '2026-09-01', dataPgto: null }, '2', '2026-08-01');
    expect(c.valor).toBe(10.01);
  });
});

describe('editarConta (UPDATE do salvar.php)', () => {
  it('preserva data_lanc, tipo e usuario_lanc (o UPDATE do PHP não os toca)', () => {
    const original = criarConta(
      { descricao: 'Energia', valor: 150, dataVenc: '2026-09-05', dataPgto: '2026-08-01' },
      '2',
      '2026-08-01',
    );
    const editada = editarConta(original, { valor: 180, descricao: 'Energia (nova conta)' });
    expect(editada.dataLanc).toBe('2026-08-01');
    expect(editada.tipo).toBe('Conta');
    expect(editada.usuarioLanc).toBe('2');
    expect(editada.valor).toBe(180);
  });

  it('limpar dataPgto deixa a conta pendente (pago derivado — o PHP deixaria pago="Sim" inconsistente)', () => {
    const original = criarConta(
      { descricao: 'Energia', valor: 150, dataVenc: '2026-09-05', dataPgto: '2026-08-01' },
      '2',
      '2026-08-01',
    );
    const editada = editarConta(original, { dataPgto: null });
    expect(editada.dataPgto).toBeNull();
    expect(contaPaga(editada)).toBe(false);
  });
});

describe('baixarConta (pagar/baixar.php)', () => {
  it('baixa: dataPgto = hoje e usuario_baixa = quem baixou', () => {
    const conta = criarConta({ descricao: 'Energia', valor: 150, dataVenc: '2026-09-05', dataPgto: null }, '2', '2026-08-01');
    const baixada = baixarConta(conta, '3', '2026-08-20');
    expect(baixada.dataPgto).toBe('2026-08-20');
    expect(baixada.usuarioBaixa).toBe('3');
    expect(contaPaga(baixada)).toBe(true);
  });

  it('INVARIANTE: não baixa conta já baixada', () => {
    const paga = criarConta({ descricao: 'Energia', valor: 150, dataVenc: '2026-09-05', dataPgto: '2026-08-01' }, '2', '2026-08-01');
    expect(() => baixarConta(paga, '3', '2026-08-20')).toThrow(ContaInvalidaError);
  });
});

describe('baixarReceber (receber/baixar.php)', () => {
  it('receber tipo Conta: só baixa, NÃO gera comissão', () => {
    const conta = { ...contaServico(), tipo: 'Conta' as const };
    const { conta: baixada, comissao } = baixarReceber(conta, corte, 'Porcentagem', '3', '2026-08-20');
    expect(baixada.dataPgto).toBe('2026-08-20');
    expect(comissao).toBeUndefined();
  });

  it('receber tipo Serviço: baixa e gera a comissão a pagar do funcionário', () => {
    const { conta: baixada, comissao } = baixarReceber(contaServico(), corte, 'Porcentagem', '3', '2026-08-20');

    expect(baixada.dataPgto).toBe('2026-08-20');

    expect(comissao).toBeDefined();
    expect(comissao!.descricao).toBe('Comissão - Corte');
    expect(comissao!.tipo).toBe('Comissão');
    expect(comissao!.valor).toBe(3.5); // 10% de R$ 35,00
    expect(comissao!.dataLanc).toBe('2026-08-20');
    expect(comissao!.dataVenc).toBe('2026-08-20'); // legado: data_venc = curDate()
    expect(comissao!.dataPgto).toBeNull(); // comissão sempre nasce pendente
    expect(contaPaga(comissao!)).toBe(false);
    expect(comissao!.funcionario).toBe('11');
    expect(comissao!.servico).toBe('1');
    expect(comissao!.cliente).toBe('5'); // legado copia o pessoa do receber
    expect(comissao!.usuarioLanc).toBe('3');
  });

  it('comissão em modo R$ usa o valor fixo do serviço', () => {
    const { comissao } = baixarReceber(contaServico(), corte, 'R$', '3', '2026-08-20');
    expect(comissao!.valor).toBe(10);
  });

  it('INVARIANTE: uma comissão por receber — dupla baixa é rejeitada (PHP criava 2)', () => {
    const baixada = baixarReceber(contaServico(), corte, 'Porcentagem', '3', '2026-08-20').conta;
    expect(() => baixarReceber(baixada, corte, 'Porcentagem', '3', '2026-08-20')).toThrow(ContaInvalidaError);
  });
});

describe('resumoContas (listar.php)', () => {
  const paga = (v: number, venc: string) =>
    ({ descricao: 'p', tipo: 'Conta', valor: v, dataLanc: '2026-01-01', dataVenc: venc, dataPgto: '2026-01-10' }) as Conta;
  const pendente = (v: number, venc: string) =>
    ({ descricao: 'p', tipo: 'Conta', valor: v, dataLanc: '2026-01-01', dataVenc: venc, dataPgto: null }) as Conta;

  it('separa totais pagos e pendentes', () => {
    const r = resumoContas([paga(100, '2026-01-05'), pendente(40, '2026-02-01'), pendente(60, '2026-02-01')], '2026-01-20');
    expect(r.totalPago).toBe(100);
    expect(r.totalPendente).toBe(100);
  });

  it('vencida = pendente com data_venc < hoje (igual ao vermelho-escuro do PHP)', () => {
    const vencida = pendente(50, '2026-01-05');
    const futura = pendente(30, '2026-02-01');
    const pagaAtrasada = paga(20, '2025-12-01'); // atrasada mas paga NÃO é vencida
    const r = resumoContas([vencida, futura, pagaAtrasada], '2026-01-20');
    expect(r.vencidas).toEqual([vencida]);
    expect(r.totalVencidas).toBe(50);
  });

  it('lista vazia = zeros', () => {
    const r = resumoContas([], '2026-01-20');
    expect(r.totalPago).toBe(0);
    expect(r.totalPendente).toBe(0);
    expect(r.totalVencidas).toBe(0);
    expect(r.vencidas).toEqual([]);
  });
});

describe('saldo (novo, derivado dos totais)', () => {
  it('receber pendente − pagar pendente', () => {
    const receber = [{ descricao: 'r', tipo: 'Conta', valor: 300, dataLanc: '2026-01-01', dataVenc: '2026-02-01', dataPgto: null }] as Conta[];
    const pagar = [{ descricao: 'p', tipo: 'Comissão', valor: 30, dataLanc: '2026-01-01', dataVenc: '2026-01-05', dataPgto: null }] as Conta[];
    expect(saldo(receber, pagar)).toBe(270);
  });

  it('contas pagas não entram no saldo', () => {
    const receber = [{ descricao: 'r', valor: 300, dataVenc: '2026-02-01', dataPgto: '2026-01-10' }] as Conta[];
    expect(saldo(receber, [])).toBe(0);
  });
});
