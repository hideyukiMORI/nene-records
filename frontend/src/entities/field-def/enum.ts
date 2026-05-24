export const FIELD_DATA_TYPES = ['text', 'int', 'enum', 'bool', 'datetime'] as const

export type FieldDataType = (typeof FIELD_DATA_TYPES)[number]

export function isFieldDataType(value: string): value is FieldDataType {
  return (FIELD_DATA_TYPES as readonly string[]).includes(value)
}
