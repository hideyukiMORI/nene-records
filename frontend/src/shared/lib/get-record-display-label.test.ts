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
