import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

// SECURITY: force RUNTIME-ONLY builds (no runtime compilers → no `new Function()`).
//
// The prod CSP forbids 'unsafe-eval', so the browser must never call `new
// Function()` / `eval()`. Two libraries otherwise bundle a runtime compiler that
// does exactly that:
//
//   1. Vue — all templates are .vue SFCs compiled at build time by
//      @vitejs/plugin-vue, so pin 'vue' to the runtime-only bundle (excludes the
//      template compiler).
//   2. vue-i18n — its full build bundles @intlify/message-compiler, which calls
//      `new Function()` for runtime message compilation. Our messages are
//      imported from JSON at build time and use only simple :named
//      interpolation, so pin 'vue-i18n' to the runtime-only bundle (no compiler).
//
// Do NOT import the full builds (vue/dist/vue.esm-bundler.js or
// vue-i18n/dist/vue-i18n.esm-bundler.js) anywhere in the app.
const nm = (p: string) => fileURLToPath(new URL(`./node_modules/${p}`, import.meta.url));

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      vue: nm('vue/dist/vue.runtime.esm-bundler.js'),
      'vue-i18n': nm('vue-i18n/dist/vue-i18n.runtime.esm-bundler.js'),
    },
  },
  server: { host: true, port: 5173, strictPort: true, hmr: { host: 'localhost' } },
  build: {
    outDir: '../public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: { input: 'src/main.ts' },
  },
});
