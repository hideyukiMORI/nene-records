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
    // Relative asset URLs so the built SPA works from any sub-directory at
    // runtime (#zip-install S2). The injected `<base href>` anchors them to the
    // configured base path; internal chunk imports resolve next to the entry.
    base: './',
    plugins: [react(), tailwindcss()],
    build: {
      // Emit dist/.vite/manifest.json so the PHP single-origin SSR can resolve
      // the hashed entry JS/CSS and mount the built SPA on server-rendered pages.
      manifest: true,
      rollupOptions: {
        output: {
          // Split long-lived vendor code out of the app entry so the initial
          // chunk holds app code only (smaller entry + better long-term caching).
          manualChunks(id: string) {
            if (!id.includes('node_modules')) {
              return undefined
            }
            if (id.includes('@tanstack')) {
              return 'vendor-query'
            }
            if (id.includes('react-router') || id.includes('/history/')) {
              return 'vendor-router'
            }
            if (
              id.includes('/react-dom/') ||
              id.includes('/react/') ||
              id.includes('/scheduler/')
            ) {
              return 'vendor-react'
            }
            if (id.includes('dompurify') || id.includes('marked') || id.includes('micromark')) {
              return 'vendor-markdown'
            }
            return 'vendor'
          },
        },
      },
    },
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
