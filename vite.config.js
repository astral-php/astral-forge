import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [tailwindcss(), vue()],
  // Évite le conflit outDir ⊂ public/
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        app: 'resources/js/app.js',
        style: 'resources/css/app.css',
      },
    },
    minify: 'esbuild',
    sourcemap: false,
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
    hmr: { host: 'localhost' },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
})
