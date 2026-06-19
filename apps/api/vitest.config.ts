import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'node',
    exclude: [
      'node_modules',
      'dist',
      // Skip RLS integration test when no real DB is available
      ...(process.env.VITEST_SKIP_RLS === 'true' ? ['src/db/rls.test.ts'] : []),
    ],
  },
});
