import { describe, expect, it } from 'vitest';
import {
  excedeLimite,
  funcionalidadeHabilitada,
  limitesDe,
  PlanoInvalidoError,
  verificarLimites,
} from '../src/plans.js';

describe('limites por plano (tabela do SAAS_PLANO)', () => {
  it('Grátis: 50 agendamentos/mês e 2 funcionários', () => {
    expect(limitesDe('Gratis')).toEqual({
      agendamentosPorMes: 50,
      funcionarios: 2,
      financeiro: false,
      estoque: false,
      dominioProprio: false,
    });
  });

  it('Pro: 500 agendamentos, 5 funcionários, financeiro liberado', () => {
    expect(limitesDe('Pro')).toMatchObject({
      agendamentosPorMes: 500,
      funcionarios: 5,
      financeiro: true,
      estoque: false,
      dominioProprio: false,
    });
  });

  it('Premium: ilimitado em tudo', () => {
    expect(limitesDe('Premium')).toEqual({
      agendamentosPorMes: null,
      funcionarios: null,
      financeiro: true,
      estoque: true,
      dominioProprio: true,
    });
  });

  it('funcionalidadeHabilitada reflete os recursos por plano', () => {
    expect(funcionalidadeHabilitada('Gratis', 'financeiro')).toBe(false);
    expect(funcionalidadeHabilitada('Pro', 'financeiro')).toBe(true);
    expect(funcionalidadeHabilitada('Pro', 'estoque')).toBe(false);
    expect(funcionalidadeHabilitada('Premium', 'dominioProprio')).toBe(true);
  });
});

describe('verificarLimites (enforcement na ação)', () => {
  it('uso abaixo/igual ao limite não excede', () => {
    expect(verificarLimites('Gratis', { agendamentosMes: 50 })).toEqual({
      excede: false,
      violacoes: [],
    });
    expect(verificarLimites('Gratis', { funcionarios: 2, agendamentosMes: 49 })).toEqual({
      excede: false,
      violacoes: [],
    });
  });

  it('INVARIANTE: 1 acima do limite já excede (limite duro, sem folga)', () => {
    const r = verificarLimites('Gratis', { agendamentosMes: 51 });
    expect(r.excede).toBe(true);
    expect(r.violacoes).toHaveLength(1);
    expect(r.violacoes[0]).toMatchObject({ recurso: 'agendamentosMes', limite: 50, uso: 51 });
  });

  it('reporta todas as violações de uma vez', () => {
    const r = verificarLimites('Pro', { agendamentosMes: 600, funcionarios: 7 });
    expect(r.excede).toBe(true);
    expect(r.violacoes.map((v) => v.recurso)).toEqual(['agendamentosMes', 'funcionarios']);
  });

  it('Premium nunca excede (limites null = ilimitado)', () => {
    expect(excedeLimite('Premium', { agendamentosMes: 999999, funcionarios: 100 })).toBe(false);
    expect(verificarLimites('Premium', { agendamentosMes: 1 })).toEqual({
      excede: false,
      violacoes: [],
    });
  });

  it('uso vazio não viola nada', () => {
    expect(verificarLimites('Gratis').excede).toBe(false);
  });
});

describe('validação de entrada', () => {
  it('plano desconhecido lança erro', () => {
    expect(() => limitesDe('Inexistente' as never)).toThrow(PlanoInvalidoError);
  });

  it('uso negativo ou fracionário é inválido', () => {
    expect(() => verificarLimites('Gratis', { agendamentosMes: -1 })).toThrow(PlanoInvalidoError);
    expect(() => verificarLimites('Gratis', { funcionarios: 2.5 })).toThrow(PlanoInvalidoError);
  });
});
