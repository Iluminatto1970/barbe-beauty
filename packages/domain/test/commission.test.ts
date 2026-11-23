import { describe, expect, it } from 'vitest';
import {
  arredondar2,
  calcularComissao,
  ComissaoInvalidaError,
  totalComissoes,
} from '../src/commission.js';

// Dados reais do seed (sql/barbearia.sql): comissão é decimal(8,2) e
// config.tipo_comissao decide Porcentagem (%) ou R$ (fixo).
const corte = { nome: 'Corte', valor: 35, comissao: 10 };
const barba = { nome: 'Barba', valor: 30, comissao: 10 };
const corteBarba = { nome: 'Corte + Barba', valor: 60, comissao: 15 };

describe('cálculo da comissão', () => {
  it('Porcentagem: 10% sobre R$ 35,00 = R$ 3,50', () => {
    expect(calcularComissao(corte, 'Porcentagem')).toBe(3.5);
  });

  it('Porcentagem: 15% sobre R$ 60,00 = R$ 9,00', () => {
    expect(calcularComissao(corteBarba, 'Porcentagem')).toBe(9);
  });

  it('R$: usa o valor fixo da comissão do serviço', () => {
    expect(calcularComissao(corte, 'R$')).toBe(10);
  });

  it('arredonda para 2 casas (half-up, como o decimal(8,2) do MySQL)', () => {
    expect(calcularComissao({ nome: 'Teste', valor: 34.99, comissao: 10 }, 'Porcentagem')).toBe(3.5);
    expect(arredondar2(3.14159)).toBe(3.14);
    expect(arredondar2(3.145)).toBe(3.15);
  });

  it('serviço gratuito gera comissão zero', () => {
    expect(calcularComissao({ nome: 'Brinde', valor: 0, comissao: 10 }, 'Porcentagem')).toBe(0);
  });

  it('percentual > 100 passa sem clamp (comportamento fiel ao PHP)', () => {
    expect(calcularComissao({ nome: 'Corte VIP', valor: 35, comissao: 200 }, 'Porcentagem')).toBe(70);
  });

  it('INVARIANTE: com percentual <= 100, a comissão nunca excede o valor do serviço', () => {
    const amostras = [
      { nome: 'a', valor: 120, comissao: 10 },
      { nome: 'b', valor: 79.9, comissao: 15 },
      { nome: 'c', valor: 45.55, comissao: 50 },
      { nome: 'd', valor: 12.34, comissao: 100 },
    ];
    for (const servico of amostras) {
      expect(calcularComissao(servico, 'Porcentagem')).toBeLessThanOrEqual(servico.valor);
    }
  });
});

describe('validação de entrada', () => {
  it('rejeita valor negativo', () => {
    expect(() => calcularComissao({ nome: 'X', valor: -1, comissao: 10 }, 'Porcentagem')).toThrow(
      ComissaoInvalidaError,
    );
  });

  it('rejeita comissão negativa', () => {
    expect(() => calcularComissao({ nome: 'X', valor: 35, comissao: -5 }, 'Porcentagem')).toThrow(
      ComissaoInvalidaError,
    );
  });

  it('rejeita valor não numérico', () => {
    expect(() => calcularComissao({ nome: 'X', valor: Number.NaN, comissao: 10 }, 'Porcentagem')).toThrow(
      ComissaoInvalidaError,
    );
  });
});

describe('total de comissões', () => {
  it('soma as comissões do dia', () => {
    expect(totalComissoes([corte, barba], 'Porcentagem')).toBe(6.5);
  });

  it('total vazio é zero', () => {
    expect(totalComissoes([], 'Porcentagem')).toBe(0);
  });

  it('soma em modo R$ usa os valores fixos', () => {
    expect(totalComissoes([corte, corteBarba], 'R$')).toBe(25);
  });
});
