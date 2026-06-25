/**
 * Runtime base path (#zip-install S2). The app can be served from a sub-directory
 * (e.g. `https://example.com/blog/`); the server emits a `<base href="{base}/">`
 * (which also anchors the SPA's relative asset URLs), and the SPA derives the base
 * from it here to set the router basename and prefix API requests.
 *
 * Reading the `<base>` element — rather than an injected inline script — keeps the
 * strict public CSP (`default-src 'self'`, no `script-src 'unsafe-inline'`) intact.
 * Default `''` = served at root.
 */

/** The configured base path (`''` or `/segment`), derived from `<base href>`. */
export function getBasePath(): string {
  if (typeof document === 'undefined') {
    return ''
  }

  const href = document.querySelector('base')?.getAttribute('href')

  if (href === null || href === undefined) {
    return ''
  }

  try {
    return normalizeBasePath(new URL(href, window.location.origin).pathname)
  } catch {
    return ''
  }
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
