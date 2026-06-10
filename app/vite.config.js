import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));

/**
 * Admin (wp-admin) build: Vue apps served as ES modules.
 * The checkout frontend bundle has its own config (vite.checkout.config.js).
 */
export default defineConfig({
  base: './',
  plugins: [vue()],
  build: {
    outDir: resolve(__dirname, 'dist'),
    // Never wipe dist on admin-only builds: dist/checkout belongs to the
    // checkout build (vite.checkout.config.js). The full "build" script
    // passes --emptyOutDir explicitly to clean stale hashed chunks.
    emptyOutDir: false,
    manifest: true,
    sourcemap: false,
    rollupOptions: {
      input: {
        settings: resolve(__dirname, 'src/entries/settings.js'),
      },
      output: {
        entryFileNames: '[name]/app.js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'styles/[name][extname]';
          }

          return 'assets/[name]-[hash][extname]';
        },
      },
    },
  },
});
