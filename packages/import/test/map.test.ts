import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { importarDump } from '../src/index.js';

// ESM: caminho absoluto a partir do próprio módulo
const DUMP_REAL = readFileSync(
  new URL('../../../sql/barbearia.sql', import.meta.url),
  'utf8',
);

describe('mapeamento (unidades)', () => {
  it('injetar tenant_id em toda linha de toda tabela', () => {
    const sql = [
      'INSERT INTO `servicos` (`id`, `nome`, `valor`) VALUES',
      "(1, 'Corte', '35.00');",
    ].join('\n');
    const r = importarDump(sql, 'tenant-abc');
    expect(r.tabelas.servicos![0]).toEqual({
      id: 1,
      nome: 'Corte',
      valor: '35.00',
      tenant_id: 'tenant-abc',
    });
  });

  it('sentinela 0000-00-00 vira null nos campos de data mapeados', () => {
    const sql = [
      'INSERT INTO `clientes` (`id`, `nome`, `data_nasc`, `data_cad`, `data_retorno`) VALUES',
      "(15, 'Fernando', '0000-00-00', '2025-07-11', NULL);",
    ].join('\n');
    const r = importarDump(sql, 't1');
    expect(r.tabelas.clientes![0]).toMatchObject({
      data_nasc: null,
      data_cad: '2025-07-11',
      data_retorno: null,
    });
  });

  it("dias com 'Sabado' (bug do legado) vira 'Sábado'", () => {
    const sql = [
      'INSERT INTO `dias` (`id`, `dia`, `funcionario`) VALUES',
      "(1, 'Sabado', 11);",
    ].join('\n');
    const r = importarDump(sql, 't1');
    expect(r.tabelas.dias![0]!.dia).toBe('Sábado');
  });

  it('sentinela em tabela sem campos de data não é alterada (não há remap falso)', () => {
    const sql = [
      'INSERT INTO `produtos` (`id`, `nome`, `descricao`) VALUES',
      "(1, 'X', '0000-00-00');",
    ].join('\n');
    const r = importarDump(sql, 't1');
    expect(r.tabelas.produtos![0]!.descricao).toBe('0000-00-00');
  });
});

describe('integração contra o dump real (sql/barbearia.sql)', () => {
  const r = importarDump(DUMP_REAL, 'tenant-demo');
  const servicos = r.tabelas.servicos!;
  const config = r.tabelas.config!;
  const clientes = r.tabelas.clientes!;
  const dias = r.tabelas.dias!;

  it('parseia todas as tabelas com dados e com as contagens do arquivo', () => {
    const esperado: Record<string, number> = {
      acessos: 32,
      cargos: 4,
      cat_produtos: 5,
      cat_servicos: 1,
      clientes: 1,
      comentarios: 6,
      config: 1,
      dias: 16,
      fornecedores: 3,
      grupo_acessos: 7,
      horarios: 26,
      produtos: 4,
      servicos: 3,
      textos_index: 2,
      usuarios: 2,
    };
    for (const [tabela, n] of Object.entries(esperado)) {
      expect(r.tabelas[tabela]).toHaveLength(n);
    }
  });

  it('toda linha de toda tabela tem tenant_id', () => {
    for (const linhas of Object.values(r.tabelas)) {
      for (const linha of linhas) {
        expect(linha.tenant_id).toBe('tenant-demo');
      }
    }
  });

  it('serviços reais do seed preservados (id, nome, valor, comissão)', () => {
    expect(servicos.map((s) => s.nome)).toEqual(['Corte', 'Barba', 'Corte + Barba']);
    expect(servicos[0]).toMatchObject({
      id: 1,
      valor: '35.00',
      comissao: '10.00',
      ativo: 'Sim',
      dias_retorno: 15,
    });
  });

  it('config única mapeada com tipo_comissao e quantidade_cartoes reais', () => {
    expect(config[0]).toMatchObject({
      id: 2,
      nome: 'Barbearia PRO',
      tipo_comissao: 'R$',
      quantidade_cartoes: 10,
    });
  });

  it('o sentinela real do dump (data_nasc do cliente) vira null', () => {
    expect(clientes[0]).toMatchObject({
      nome: 'Fernando Machado',
      data_nasc: null,
    });
  });

  it('dias sem o bug ficam intactos e os acentos preservados', () => {
    const diasNomes = dias.map((d) => d.dia as string);
    expect(diasNomes).toContain('Segunda-Feira');
    expect(diasNomes.every((dia) => !/\bSabado\b/.test(dia))).toBe(true);
    expect(diasNomes).toContain('Sábado');
  });

  it('mapa da config (iframe com aspas escapadas) é desescapado corretamente', () => {
    const mapa = config[0]!.mapa as string;
    expect(mapa).toContain('width="600"');
    expect(mapa).toContain('https://www.google.com/maps/embed');
    expect(mapa).not.toContain('\\"');
  });
});
