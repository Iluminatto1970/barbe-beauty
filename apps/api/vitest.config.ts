import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    // O primeiro import do Fastify pode demorar (cold start/transform)
    testTimeout: 20000,
    hookTimeout: 20000,
  },
});
