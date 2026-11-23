import { defineConfig } from 'vitest/config';

// Testes unitários do web (repo/db/seed sobre o pg-mem).
// O e2e (Playwright) roda separado via `pnpm test:e2e`.
export default defineConfig({
  test: {
    include: ['lib/**/*.test.ts'],
  },
});
