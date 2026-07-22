/**
 * Strips a trailing `｜{siteName}` / `— {siteName}` SEO suffix from an authored
 * page title so admin lists show a short, distinct name (#990).
 *
 * Authored `meta_title`s often follow the `PageName｜SiteName` convention, which
 * reads as noise when the same site name repeats on every row of a list. This is
 * a no-op when the site name is unknown, equals the whole title, or is not a
 * trailing suffix — safe for any input, and it never empties a title.
 */
export function stripSiteNameSuffix(title: string, siteName: string): string {
  const t = title.trim()
  const s = siteName.trim()
  if (s === '' || t === s || !t.endsWith(s)) {
    return t
  }
  // Drop the site name and any separators/whitespace that joined it to the head.
  const head = t.slice(0, t.length - s.length).replace(/[\s｜|—–・:：-]+$/u, '')
  return head === '' ? t : head
}
