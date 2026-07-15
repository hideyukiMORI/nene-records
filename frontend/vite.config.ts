import { readFileSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv, type Plugin } from 'vite'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

/**
 * Ship the OFL license text next to the Zen Kaku Gothic New subset woff2 files (#872).
 *
 * OFL 1.1 §2 requires every redistributed copy to carry the copyright notice and
 * the license. Our copy satisfies none of the three allowed channels on its own:
 * `pyftsubset` strips the font's `name` table (no machine-readable metadata), and
 * CSS comments — including `/*!` legal comments — are dropped by the minifier
 * (measured: the notice does not survive `npm run build`). So the license travels
 * as a stand-alone text file emitted next to the fonts it covers.
 *
 * Emitting into `assets/` (rather than `public/`) is deliberate: the built font
 * files live there, the production Apache already aliases `/assets`, and
 * `tools/build-release.sh` copies that directory into the release ZIP verbatim —
 * so one emit covers the served site and both distribution ZIPs with no extra
 * wiring. The filename is unhashed so it stays quotable from docs and headers.
 */
function emitFontLicense(): Plugin {
  const source = path.resolve(__dirname, 'src/pages/consumer/fonts/OFL.txt')
  return {
    name: 'nene-emit-font-license',
    apply: 'build',
    generateBundle() {
      this.emitFile({
        type: 'asset',
        fileName: 'assets/OFL-ZenKakuGothicNew.txt',
        source: readFileSync(source, 'utf8'),
      })
    },
  }
}

export default defineConfig(({ mode }) => {
  // Read NENE_RECORDS_PORT from the project-root .env (one level up from frontend/).
  // This keeps the dev proxy in sync with whatever port is configured in .env
  // without duplicating the value. The fallback MUST match the dev app port
  // default in compose.yaml (`${NENE_RECORDS_PORT:-18082}`); otherwise `cp
  // .env.example .env` (which leaves NENE_RECORDS_PORT commented) makes the proxy
  // target :8080 while the API is on :18082, so every /api call 502s.
  const projectEnv = loadEnv(mode, path.resolve(__dirname, '..'), '')
  const appPort = projectEnv['NENE_RECORDS_PORT'] ?? '18082'
  const target = `http://localhost:${appPort}`

  const frontendPort = parseInt(projectEnv['NENE_RECORDS_FRONTEND_PORT'] ?? '18084', 10)

  return {
    // Relative asset URLs so the built SPA works from any sub-directory at
    // runtime (#zip-install S2). The injected `<base href>` anchors them to the
    // configured base path; internal chunk imports resolve next to the entry.
    base: './',
    plugins: [react(), tailwindcss(), emitFontLicense()],
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
