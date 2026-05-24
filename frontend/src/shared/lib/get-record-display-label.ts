import type { TextField } from '@/entities/text-field'

export function getRecordDisplayLabel(
  entityId: number,
  textFields: TextField[],
  fallback: string,
): string {
  const forEntity = textFields.filter((item) => item.entityId === entityId)

  const titleField = forEntity.find((item) => item.fieldKey === 'title')
  if (titleField !== undefined && titleField.value.trim() !== '') {
    return titleField.value.trim()
  }

  const firstWithValue = forEntity.find((item) => item.value.trim() !== '')
  if (firstWithValue !== undefined) {
    return firstWithValue.value.trim()
  }

  return fallback
}
