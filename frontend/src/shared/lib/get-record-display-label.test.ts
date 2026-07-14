import { describe, expect, it } from 'vitest'
import { type TextField, toTextFieldId } from '@/entities/text-field'
import { getRecordDisplayLabel } from './get-record-display-label'

let nextId = 1
const tf = (
  entityId: number,
  fieldKey: string,
  value: string,
  locale: string | null = null,
): TextField => ({
  id: toTextFieldId(nextId++),
  entityId,
  fieldKey,
  value,
  locale,
})

describe('getRecordDisplayLabel', () => {
  it('returns the title field, else the first non-empty field, else the fallback', () => {
    expect(getRecordDisplayLabel(1, [tf(1, 'title', 'Hello')], 'fb')).toBe('Hello')
    expect(getRecordDisplayLabel(1, [tf(1, 'body', 'Body only')], 'fb')).toBe('Body only')
    expect(getRecordDisplayLabel(1, [], 'fb')).toBe('fb')
    expect(getRecordDisplayLabel(1, [tf(1, 'title', '   ')], 'fb')).toBe('fb')
  })

  it('ignores other entities', () => {
    expect(getRecordDisplayLabel(1, [tf(2, 'title', 'Other')], 'fb')).toBe('fb')
  })

  describe('with a requested locale', () => {
    const fields = [
      tf(1, 'title', 'Hello', null),
      tf(1, 'title', 'Hallo', 'de'),
      tf(1, 'title', 'Bonjour', 'fr'),
    ]

    it('prefers the requested locale', () => {
      expect(getRecordDisplayLabel(1, fields, 'fb', 'de')).toBe('Hallo')
      expect(getRecordDisplayLabel(1, fields, 'fb', 'fr')).toBe('Bonjour')
    })

    it('falls back to the locale-agnostic (null) row when the locale is missing', () => {
      expect(getRecordDisplayLabel(1, fields, 'fb', 'zh-Hans')).toBe('Hello')
    })

    it('falls back to the first available when there is no null row either', () => {
      const noBase = [tf(1, 'title', 'Hallo', 'de'), tf(1, 'title', 'Bonjour', 'fr')]
      expect(getRecordDisplayLabel(1, noBase, 'fb', 'zh-Hans')).toBe('Hallo')
    })

    it('without a locale keeps the original first-match behavior', () => {
      // No locale arg → first matching title row, regardless of locale tags.
      expect(getRecordDisplayLabel(1, fields, 'fb')).toBe('Hello')
    })
  })
})

describe('derived (non-title) fallback normalization (#849)', () => {
  it('strips markup and collapses whitespace', () => {
    const html = '<header style="position:sticky"><a href="/">AYANE</a>\n  Home</header>'
    expect(getRecordDisplayLabel(1, [tf(1, 'content', html)], 'fb')).toBe('AYANE Home')
  })

  it('caps a long derived label at 120 chars with an ellipsis', () => {
    const long = 'word '.repeat(100)
    const label = getRecordDisplayLabel(1, [tf(1, 'content', long)], 'fb')
    expect(label.length).toBeLessThanOrEqual(121)
    expect(label.endsWith('…')).toBe(true)
  })

  it('falls back when the field is markup only', () => {
    expect(getRecordDisplayLabel(1, [tf(1, 'content', '<div><span></span></div>')], 'fb')).toBe(
      'fb',
    )
  })

  it('never normalizes an explicit title field', () => {
    const title = 'A <b>literal</b> title kept as-is'
    expect(getRecordDisplayLabel(1, [tf(1, 'title', title)], 'fb')).toBe(title)
  })
})
