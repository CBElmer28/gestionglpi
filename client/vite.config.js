import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      // Opcional: proxy para evitar CORS en desarrollo
      // '/api': { target: 'http://localhost:8000', changeOrigin: true },
    },
  },
  test: {
    globals: true,
    environment: 'happy-dom',
    css: false,
    include: ['src/**/*.test.js'],
    setupFiles: ['./src/tests/setup.js'],
    reporters: ['default', ['allure-vitest/reporter', { outputFolder: 'allure-results' }]],
    coverage: {
      reporter: ['text', 'json', 'html'],
      exclude: ['node_modules/', 'src/tests/setup.js'],
    },
  },
})
