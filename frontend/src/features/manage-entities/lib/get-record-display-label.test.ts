import { describe, expect, it } from 'vitest'
import { toTextFieldId } from '@/entities/text-field'
import { getRecordDisplayLabel } from './get-record-display-label'

describe('getRecordDisplayLabel', () => {
  it('prefers title field value', () => {
    const label = getRecordDisplayLabel(
      1,
      [
        {
          id: toTextFieldId(1),
          entityId: 1,
          fieldKey: 'body',
          value: 'Body text',
        },
        {
          id: toTextFieldId(2),
          entityId: 1,
          fieldKey: 'title',
          value: 'My article',
        },
      ],
      'Record #1',
    )

    expect(label).toBe('My article')
  })

  it('falls back to first non-empty text field', () => {
    const label = getRecordDisplayLabel(
      2,
      [
        {
          id: toTextFieldId(3),
          entityId: 2,
          fieldKey: 'summary',
          value: 'Short summary',
        },
      ],
      'Record #2',
    )

    expect(label).toBe('Short summary')
  })

  it('uses fallback when no values exist', () => {
    expect(getRecordDisplayLabel(3, [], 'Record #3')).toBe('Record #3')
  })
})
