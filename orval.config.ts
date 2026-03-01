import { defineConfig } from 'orval';

export default defineConfig({
  wallyApi: {
    input: {
      target: 'http://localhost:3100/api/docs-json',
    },
    output: {
      target: './libs/api-client/src/generated/index.ts',
      schemas: './libs/api-client/src/generated/model',
      client: 'fetch',
      mode: 'split',
      override: {
        mutator: {
          path: './libs/api-client/src/mutator/wally-fetch.ts',
          name: 'wallyFetch',
        },
      },
    },
  },
});
