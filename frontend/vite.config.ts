import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv } from 'vite'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig(({ mode }) => {
  // Read NENE_RECORDS_PORT from the project-root .env (one level up from frontend/).
  // This keeps the dev proxy in sync with whatever port is configured in .env
  // without duplicating the value.
  const projectEnv = loadEnv(mode, path.resolve(__dirname, '..'), '')
  const appPort = projectEnv['NENE_RECORDS_PORT'] ?? '8080'
  const target = `http://localhost:${appPort}`

  const frontendPort = parseInt(projectEnv['NENE_RECORDS_FRONTEND_PORT'] ?? '18084', 10)

  return {
    plugins: [react(), tailwindcss()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
        '@tests': path.resolve(__dirname, './tests'),
      },
    },
    server: {
      port: frontendPort,
      proxy: {
        '/api': { target, changeOrigin: true },
        '/health': { target, changeOrigin: true },
        '/view': { target, changeOrigin: true },
        // Media files served by ServeMediaHandler (GET /media/{year}/{month}/{filename})
        '/media': { target, changeOrigin: true },
      },
    },
  }
})
