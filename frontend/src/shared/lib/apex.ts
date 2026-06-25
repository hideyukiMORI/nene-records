/**
 * Subdomain SaaS apex detection. The server (SpaShellFallback) injects
 * `<meta name="nene:apex" content="1">` only on the bare base domain
 * (`nene-records.com`), so the SPA shows the global landing there instead of a
 * tenant home. Reading a meta — not an inline script — keeps the strict CSP.
 */
export function isApex(): boolean {
  if (typeof document === 'undefined') {
    return false
  }

  return document.querySelector('meta[name="nene:apex"]')?.getAttribute('content') === '1'
}
