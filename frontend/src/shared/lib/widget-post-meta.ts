/**
 * Shared helper for the post-list widgets (recent / popular): a locale-aware
 * published-date line. The excerpt itself is computed server-side now
 * (`?include=excerpt`), so widgets only format the date here.
 */

/** Format an ISO timestamp for the locale; '' when missing / unparseable. */
export function formatPostDate(iso: string | null, locale: string): string {
  if (iso === null || iso === '') {
    return ''
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return date.toLocaleDateString(locale, { year: 'numeric', month: 'short', day: 'numeric' })
}
