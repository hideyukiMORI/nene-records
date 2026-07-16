/**
 * Composes the public document title from a page title and the site name (#909).
 *
 * Twin: `src/PublicRecord/PublicDocumentTitle.php` — the SSR emits the same
 * rule, so hydration and client navigation must never change the string the
 * server rendered. Change both together.
 */
export function composePublicDocumentTitle(pageTitle: string | null, siteName: string): string {
  const page = (pageTitle ?? '').trim()
  const site = siteName.trim()

  if (page === '') {
    return site
  }

  if (site === '' || page.includes(site)) {
    return page
  }

  return `${page} — ${site}`
}
