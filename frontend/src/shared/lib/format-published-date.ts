/**
 * Formats an ISO timestamp as a human-readable published date
 * (e.g. "June 23, 2026"). Returns '' for null / empty / unparseable input.
 *
 * Shared by the public home / browse / record-detail views (WS-17): a single
 * source of truth instead of three identical local copies.
 */
export function formatPublishedDate(iso: string | null): string {
  if (iso === null || iso === '') {
    return ''
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}
