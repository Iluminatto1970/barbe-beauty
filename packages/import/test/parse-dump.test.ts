import { describe, expect, it } from 'vitest';
import { desescapar, parseDump, splitValues } from '../src/parse-dump.js';

describe('splitValues', () => {
  it('separa por vírgula respeitando aspas', () => {
    expect(splitValues(`1, 'Corte', 35.00, 'a, b'`)).toEqual(["1", "'Corte'", "35.00", "'a, b'"]);
  });

  it('não quebra com aspas escapadas dentro de string', () => {
    expect(splitValues(`'diz \\'oi\\'', 2`)).toEqual(["'diz \\'oi\\''", "2"]);
  });
});

describe('desescapar', () => {
  it('desescapa aspas e barra invertida do MySQL', () => {
    expect(desescapar(`a\\'b`)).toBe(`a'b`);
    expect(desescapar(`a\\\\b`)).toBe(`a\\b`);
    expect(desescapar(`\\"aspas\\"`)).toBe(`"aspas"`);
  });
});

describe('parseDump', () => {
  it('parseia INSERTs multi-linha com tipos', () => {
    const sql = [
      'INSERT INTO `servicos` (`id`, `nome`, `valor`, `ativo`, `foto`) VALUES',
      "(1, 'Corte', '35.00', 'Sim', '27-08-2025-corte.png'),",
      "(2, 'Barba', 30.00, 'Sim', NULL);",
    ].join('\n');

    const tabela = parseDump(sql)[0]!;
    expect(tabela.nome).toBe('servicos');
    expect(tabela.colunas).toEqual(['id', 'nome', 'valor', 'ativo', 'foto']);
    expect(tabela.linhas).toHaveLength(2);
    expect(tabela.linhas[0]).toEqual([1, 'Corte', '35.00', 'Sim', '27-08-2025-corte.png']);
    expect(tabela.linhas[1]).toEqual([2, 'Barba', 30.0, 'Sim', null]);
  });

  it('string com vírgula e aspas escapadas é preservada inteira', () => {
    const sql = "INSERT INTO `x` (`a`) VALUES\n('texto com, vírgula e \\'aspas\\'');\n";
    const tabela = parseDump(sql)[0]!;
    expect(tabela.linhas[0]![0]).toBe("texto com, vírgula e 'aspas'");
  });

  it('linha com número errado de valores lança erro (defesa do parser)', () => {
    const sql = 'INSERT INTO `x` (`a`, `b`) VALUES\n(1);\n';
    expect(() => parseDump(sql)).toThrow(/esperava 2/);
  });

  it('tabelas sem dados não aparecem', () => {
    const sql = '-- apenas comentário\n\nINSERT INTO `config` (`id`) VALUES\n(1);\n';
    expect(parseDump(sql)).toHaveLength(1);
  });
});
