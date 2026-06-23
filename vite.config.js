import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        app: 'public/assets/js/app.js',
        style: 'public/assets/css/app.css'
      }
    }
  }
});
