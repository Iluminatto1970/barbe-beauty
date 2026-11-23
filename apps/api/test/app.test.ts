import { describe, expect, it } from 'vitest';
import { buildServer } from '../src/app.js';

describe('GET /health', () => {
  it('responde ok', async () => {
    const app = buildServer();
    const res = await app.inject({ method: 'GET', url: '/health' });

    expect(res.statusCode).toBe(200);
    expect(res.json()).toMatchObject({ status: 'ok', servico: 'barbearia-api' });

    await app.close();
  });
});
