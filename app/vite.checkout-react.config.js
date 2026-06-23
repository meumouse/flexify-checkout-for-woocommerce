import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));

/**
 * React checkout frontend build.
 *
 * Emits a single classic (IIFE) bundle with React bundled in, enqueued with
 * wp_enqueue_script on the checkout page when the React checkout mode is on.
 * Output: app/dist/checkout-react/main.js + main.css.
 */
export default defineConfig({
  base: './',
  plugins: [react()],
  build: {
    outDir: resolve(__dirname, 'dist/checkout-react'),
    emptyOutDir: true,
    sourcemap: false,
    cssCodeSplit: false,
    lib: {
      entry: resolve(__dirname, 'src/checkout-react/index.jsx'),
      name: 'FlexifyReactCheckout',
      formats: ['iife'],
      fileName: () => 'main.js',
    },
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'main.css';
          }

          return 'assets/[name]-[hash][extname]';
        },
      },
    },
  },
});
