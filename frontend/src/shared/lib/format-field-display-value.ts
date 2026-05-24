import type { FieldDataType } from '@/entities/field-def'

export function formatFieldDisplayValue(
  dataType: FieldDataType,
  raw: string | number | boolean | null | undefined,
): string {
  if (raw === null || raw === undefined) {
    return '—'
  }

  switch (dataType) {
    case 'bool':
      return raw === true || raw === 'true' ? 'Yes' : 'No'
    case 'int':
      return String(raw)
    case 'datetime': {
      const iso = String(raw)
      if (iso.trim() === '') {
        return '—'
      }
      const date = new Date(iso)
      if (Number.isNaN(date.getTime())) {
        return iso
      }
      return date.toLocaleString()
    }
    default:
      return String(raw).trim() === '' ? '—' : String(raw)
  }
}
