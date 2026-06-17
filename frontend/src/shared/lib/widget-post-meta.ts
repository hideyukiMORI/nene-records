/**
 * Shared helpers for the post-list widgets (recent / popular) optional
 * date + excerpt lines. Kept tiny and locale-aware so both widgets render
 * the same way.
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

/** Trim an excerpt to at most `length` chars, adding an ellipsis when cut. */
export function truncateExcerpt(text: string, length: number): string {
  const trimmed = text.trim()
  if (length <= 0 || trimmed.length <= length) {
    return trimmed
  }
  return `${trimmed.slice(0, length).trimEnd()}…`
}
