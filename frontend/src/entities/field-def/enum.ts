export const FIELD_DATA_TYPES = [
  'text',
  'markdown',
  'int',
  'enum',
  'bool',
  'datetime',
  'image',
  'file',
  'relation',
] as const

export type FieldDataType = (typeof FIELD_DATA_TYPES)[number]

export const RELATION_CARDINALITIES = ['one', 'many'] as const

export type RelationCardinality = (typeof RELATION_CARDINALITIES)[number]

export function isFieldDataType(value: string): value is FieldDataType {
  return (FIELD_DATA_TYPES as readonly string[]).includes(value)
}

export function isRelationCardinality(value: string): value is RelationCardinality {
  return (RELATION_CARDINALITIES as readonly string[]).includes(value)
}
