import { describe, expect, it } from 'vitest'
import { formatPublishedDate } from './format-published-date'

describe('formatPublishedDate', () => {
  it('formats a valid ISO timestamp as a long date', () => {
    const result = formatPublishedDate('2026-06-23T12:00:00Z')
    // Timezone-robust assertion (noon UTC stays on the same calendar day worldwide).
    expect(result).toContain('June')
    expect(result).toContain('2026')
  })

  it('returns an empty string for null / empty / unparseable input', () => {
    expect(formatPublishedDate(null)).toBe('')
    expect(formatPublishedDate('')).toBe('')
    expect(formatPublishedDate('not-a-date')).toBe('')
  })
})
