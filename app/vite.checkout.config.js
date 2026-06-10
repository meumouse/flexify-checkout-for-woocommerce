import { defineConfig } from 'vite';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));

/**
 * Checkout frontend build.
 *
 * Emits a single classic (IIFE) bundle so the script can be enqueued with
 * wp_enqueue_script and keep its position in the dependency chain
 * (jquery, wc-checkout) without type="module" deferral side effects.
 */
export default defineConfig({
  base: './',
  build: {
    outDir: resolve(__dirname, 'dist/checkout'),
    emptyOutDir: true,
    sourcemap: false,
    lib: {
      entry: resolve(__dirname, 'src/checkout/index.js'),
      name: 'FlexifyCheckout',
      formats: ['iife'],
      fileName: () => 'main.js',
    },
    rollupOptions: {
      output: {
        // jQuery is provided by WordPress as a global.
        globals: {
          jquery: 'jQuery',
        },
      },
      external: ['jquery'],
    },
  },
});
