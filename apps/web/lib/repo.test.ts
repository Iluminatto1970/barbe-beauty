import { describe, expect, it } from 'vitest';
import {
  buscarAgendamentos,
  buscarSiteData,
  buscarTenantPorSlug,
  listarResumoAdmin,
  listarTenantsPublicos,
} from './repo';
import { garantirSeed } from './seed';

describe('repo (pg-mem) — site por tenant', () => {
  it('busca o tenant pelo slug e lista os tenants do switcher', async () => {
    await garantirSeed();
    const elite = await buscarTenantPorSlug('barbearia-elite');
    expect(elite).toMatchObject({ slug: 'barbearia-elite', nome: 'Barbearia Elite' });
    expect(await buscarTenantPorSlug('nao-existe')).toBeUndefined();
    expect(await listarTenantsPublicos()).toHaveLength(2);
  });

  it('site data do elite: dados reais do dump + identidade', async () => {
    await garantirSeed();
    const site = await buscarSiteData('barbearia-elite');
    expect(site).toBeDefined();
    expect(site!.nome).toBe('Barbearia Elite');
    expect(site!.heroTitulo).toBe('Cortes Profissionais');
    expect(site!.servicos.map((s) => s.nome)).toEqual(['Corte', 'Barba', 'Corte + Barba']);
    expect(site!.servicos[0]).toMatchObject({ valor: 35, foto: '/images/servico-corte.png' });
    expect(site!.produtos).toHaveLength(4);
    expect(site!.funcionarios.some((f) => f.nome === 'Felipe')).toBe(true);
    // Admin não aparece como barbeiro
    expect(site!.funcionarios.every((f) => f.nome !== 'Admin')).toBe(true);
  });

  it('site data do vintage: dados do mock no banco', async () => {
    await garantirSeed();
    const site = await buscarSiteData('barbearia-vintage');
    expect(site).toBeDefined();
    expect(site!.heroTitulo).toBe('Clássico nunca sai de moda');
    expect(site!.servicos.map((s) => s.nome)).toEqual(['Corte Clássico', 'Barba na Navalha', 'Corte + Barba']);
    expect(site!.funcionarios.map((f) => f.nome)).toEqual(['Diego', 'André']);
  });

  it('agendamentos de demonstração no banco (conflito do wizard)', async () => {
    await garantirSeed();
    const ags = await buscarAgendamentos('barbearia-elite');
    expect(ags.length).toBeGreaterThan(0);
    expect(ags[0]).toMatchObject({ data: expect.any(String), hora: expect.any(String), funcionarioId: expect.any(String) });
  });
});

describe('repo (pg-mem) — superadmin sem escopo de tenant', () => {
  it('lista todos os tenants com contagens (BYPASSRLS em produção)', async () => {
    await garantirSeed();
    const resumo = await listarResumoAdmin();
    expect(resumo).toHaveLength(2);
    const elite = resumo.find((r) => r.slug === 'barbearia-elite')!;
    expect(elite.servicos).toBe(3);
    expect(elite.usuarios).toBeGreaterThanOrEqual(3);
    expect(elite.comentarios).toBe(6);
    expect(elite.agendamentos).toBeGreaterThan(0);
  });
});
