import { defineConfig } from 'drizzle-kit';

/**
 * Config do Drizzle Kit. O schema alvo é Postgres (Neon em produção).
 * Defina DATABASE_URL no ambiente para apontar para o banco real;
 * sem ele, o default local é usado apenas para ferramentas offline.
 */
export default defineConfig({
  schema: './src/schema.ts',
  out: './drizzle',
  dialect: 'postgresql',
  dbCredentials: {
    url: process.env.DATABASE_URL ?? 'postgres://postgres:postgres@localhost:5432/barbearia',
  },
});
