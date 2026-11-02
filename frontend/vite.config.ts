import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  server: { host: true, port: 5173, strictPort: true, hmr: { host: 'localhost' } },
  build: { outDir: '../public/build', emptyOutDir: true, manifest: true, rollupOptions: { input: 'src/main.ts' } },
});
