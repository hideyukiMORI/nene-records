import { describe, expect, it } from 'vitest'
import { formatFieldDisplayValue } from './format-field-display-value'
import { getRecordDisplayLabel } from './get-record-display-label'
import { toTextFieldId } from '@/entities/text-field'

describe('formatFieldDisplayValue', () => {
  it('formats bool values', () => {
    expect(formatFieldDisplayValue('bool', true)).toBe('Yes')
    expect(formatFieldDisplayValue('bool', false)).toBe('No')
  })

  it('returns em dash for empty values', () => {
    expect(formatFieldDisplayValue('text', '')).toBe('—')
    expect(formatFieldDisplayValue('text', null)).toBe('—')
  })
})

describe('getRecordDisplayLabel', () => {
  it('prefers title field', () => {
    const label = getRecordDisplayLabel(
      1,
      [
        {
          id: toTextFieldId(1),
          entityId: 1,
          fieldKey: 'title',
          value: 'Hello',
          locale: null,
        },
      ],
      'Record #1',
    )

    expect(label).toBe('Hello')
  })
})
