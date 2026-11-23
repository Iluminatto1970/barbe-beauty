import { expect, test } from '@playwright/test';

const SERVICOS_ELITE = [
  { nome: 'Corte', preco: 'R$ 35,00' },
  { nome: 'Barba', preco: 'R$ 30,00' },
  { nome: 'Corte + Barba', preco: 'R$ 60,00' },
];

const PRODUTOS_ELITE = [
  'Pomada Gel Modeladora',
  'Pomada Modeladora',
  'Loção Pós Barba',
  'Creme Hidratação',
];

test.describe('landing — tenant padrão (barbearia-elite)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('hero: título, descrição e CTA', async ({ page }) => {
    await expect(
      page.getByRole('heading', { level: 1, name: 'Cortes Profissionais', exact: true }),
    ).toBeVisible();
    await expect(page.getByRole('link', { name: 'Contate-nos' })).toBeVisible();
  });

  test('serviços: os 3 cards com preços', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Nossos Serviços' })).toBeVisible();
    for (const s of SERVICOS_ELITE) {
      await expect(
        page.getByRole('heading', { level: 3, name: s.nome, exact: true }),
      ).toBeVisible();
      await expect(page.getByText(s.preco, { exact: true }).first()).toBeVisible();
    }
  });

  test('produtos: os 4 cards com "Comprar no WhatsApp"', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Nossos Produtos' })).toBeVisible();
    for (const p of PRODUTOS_ELITE) {
      await expect(page.getByRole('heading', { level: 3, name: p, exact: true })).toBeVisible();
    }
    await expect(page.getByRole('link', { name: 'Comprar no WhatsApp' }).first()).toBeVisible();
  });

  test('rodapé: endereço e telefone', async ({ page }) => {
    await expect(page.getByText('Rua Augusta, 1500 - Consolação - São Paulo/SP')).toBeVisible();
    await expect(page.getByText('(11) 3333-4444')).toBeVisible();
  });

  test('SEO: title e description do tenant', async ({ page }) => {
    await expect(page).toHaveTitle(/Barbearia Elite/);
  });
});

test.describe('troca de tenant', () => {
  test('switch no cabeçalho troca conteúdo, title e serviços', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('link', { name: 'Barbearia Vintage', exact: true }).click();

    await expect(
      page.getByRole('heading', { level: 1, name: 'Clássico nunca sai de moda', exact: true }),
    ).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Corte Clássico', exact: true })).toBeVisible();
    await expect(page).toHaveTitle(/Barbearia Vintage/);
  });

  test('?tenant=barbearia-vintage direto', async ({ page }) => {
    await page.goto('/?tenant=barbearia-vintage');
    await expect(
      page.getByRole('heading', { level: 1, name: 'Clássico nunca sai de moda', exact: true }),
    ).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Barba na Navalha', exact: true })).toBeVisible();
    await expect(page).toHaveTitle(/Barbearia Vintage/);
  });

  test('tenant desconhecido cai no padrão', async ({ page }) => {
    await page.goto('/?tenant=nao-existe');
    await expect(
      page.getByRole('heading', { level: 1, name: 'Cortes Profissionais', exact: true }),
    ).toBeVisible();
    await expect(page).toHaveTitle(/Barbearia Elite/);
  });
});

test.describe('wizard de agendamento (smoke)', () => {
  test('página carrega com o passo "Dados"', async ({ page }) => {
    await page.goto('/agendar');
    await expect(page.getByRole('heading', { name: 'Agende seu horário' })).toBeVisible();
    await expect(page.getByText('Seus dados', { exact: true })).toBeVisible();
    await expect(page.getByLabel('Nome')).toBeVisible();
    await expect(page.getByLabel('Telefone (WhatsApp)')).toBeVisible();
  });
});
