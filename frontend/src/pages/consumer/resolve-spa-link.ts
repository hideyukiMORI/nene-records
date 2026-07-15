import { normalizeBasePath } from '@/shared/lib/base-path'

/**
 * Paths the SPA router does not own — the server serves them as real responses
 * (JSON, uploaded media, built assets). Mirrors the server's edge-layer passthrough
 * in `CustomPermalinkResolver::PASSTHROUGH`.
 */
const SERVER_OWNED = /^\/(api|media|assets|theme-thumbnails)(\/|$)/

/** A path segment that looks like a file (`report.pdf`) is served, not routed. */
const LOOKS_LIKE_FILE = /\.[a-z0-9]{2,5}$/i

export interface SpaLinkContext {
  /** `document.baseURI`-derived base (`''` or `/segment`). */
  basePath: string
  /** The page's own origin. */
  origin: string
  /** Current path *including* the base, i.e. `window.location.pathname`. */
  currentPath: string
}

/**
 * Decide whether a click on an anchor should become a client-side navigation, and
 * to which router path.
 *
 * Why this exists: a `bare`/bespoke page's chrome lives inside the sanitized `html`
 * field, so its links are plain `<a href>` — a React `<Link>` cannot survive there.
 * The browser therefore does a full document load on every navigation, which remounts
 * the whole SPA each time (#885). Intercepting the click restores SPA routing for
 * content the SPA cannot author.
 *
 * Returns the router-relative path (basename stripped, so it can go straight to
 * `navigate()`), or `null` when the browser must keep the click: anything not a
 * plain left-click, another origin, a download, a real file, an `/api|/media` path,
 * or a same-page hash (which the browser scrolls natively).
 */
export function resolveSpaLink(
  anchor: HTMLAnchorElement,
  event: Pick<
    MouseEvent,
    'button' | 'metaKey' | 'ctrlKey' | 'shiftKey' | 'altKey' | 'defaultPrevented'
  >,
  ctx: SpaLinkContext,
): string | null {
  // Anything but a plain primary click is the user asking the browser for something
  // else: a new tab, a download, a context menu.
  if (event.defaultPrevented || event.button !== 0) {
    return null
  }
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
    return null
  }

  if (anchor.hasAttribute('download')) {
    return null
  }

  const target = anchor.getAttribute('target')
  if (target !== null && target !== '' && target !== '_self') {
    return null
  }

  const rel = anchor.getAttribute('rel') ?? ''
  if (rel.split(/\s+/).includes('external')) {
    return null
  }

  const href = anchor.getAttribute('href')
  if (href === null || href === '') {
    return null
  }
  // `mailto:`/`tel:`/`javascript:` never reach the router.
  if (/^[a-z][a-z0-9+.-]*:/i.test(href) && !/^https?:/i.test(href)) {
    return null
  }

  let url: URL
  try {
    url = new URL(href, ctx.origin + ctx.currentPath)
  } catch {
    return null
  }

  if (url.origin !== ctx.origin) {
    return null
  }

  const withinBase = stripBasePath(url.pathname, normalizeBasePath(ctx.basePath))

  // A same-origin link outside our base path belongs to whatever else is installed there.
  if (withinBase === null) {
    return null
  }

  if (SERVER_OWNED.test(withinBase) || LOOKS_LIKE_FILE.test(withinBase)) {
    return null
  }

  // A hash pointing at the current page: let the browser scroll to the anchor.
  if (url.hash !== '' && url.pathname === ctx.currentPath) {
    return null
  }

  return withinBase + url.search + url.hash
}

/** Router-relative path, or null when the URL sits outside the install's base path. */
function stripBasePath(pathname: string, base: string): string | null {
  if (base === '') {
    return pathname
  }

  if (pathname === base) {
    return '/'
  }

  if (pathname.startsWith(base + '/')) {
    return pathname.slice(base.length)
  }

  return null
}
