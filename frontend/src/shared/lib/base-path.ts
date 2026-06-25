/**
 * Runtime base path (#zip-install S2). The app can be served from a sub-directory
 * (e.g. `https://example.com/blog/`); the server injects `window.__BASE_PATH__`
 * (from `APP_BASE_PATH`) into the HTML, and the SPA reads it here to set the
 * router basename and prefix API requests. Default `''` = served at root.
 *
 * Asset loading is handled separately by the injected `<base href>` + Vite's
 * relative `base` — this value is only for router/navigation and fetch URLs.
 */
declare global {
  interface Window {
    __BASE_PATH__?: string
  }
}

/** The configured base path (`''` or `/segment`) from `window.__BASE_PATH__`. */
export function getBasePath(): string {
  const raw = typeof window !== 'undefined' ? window.__BASE_PATH__ : undefined

  return normalizeBasePath(typeof raw === 'string' ? raw : '')
}

/** `''` (root) or `/segment` (leading slash, no trailing slash). */
export function normalizeBasePath(raw: string): string {
  const trimmed = raw.trim().replace(/^\/+|\/+$/g, '')

  return trimmed === '' ? '' : '/' + trimmed
}

/** Prefix a root-relative path (e.g. `/api/v1/x`) with the base path. */
export function withBasePath(path: string): string {
  const base = getBasePath()

  return base === '' ? path : base + path
}
