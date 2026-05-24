import type { FieldDataType, RelationCardinality } from './enum'
import type { FieldDefId } from './ids'

export interface FieldDef {
  id: FieldDefId
  entityTypeId: number
  fieldKey: string
  dataType: FieldDataType
  targetEntityTypeId?: number
  cardinality?: RelationCardinality
}

export interface RelationFieldDef extends FieldDef {
  dataType: 'relation'
  targetEntityTypeId: number
  cardinality: RelationCardinality
}

export interface FieldDefList {
  items: FieldDef[]
  limit: number
  offset: number
}

export interface CreateFieldDefInput {
  entityTypeId: number
  fieldKey: string
  dataType: FieldDataType
  targetEntityTypeId?: number
  cardinality?: RelationCardinality
}

export type UpdateFieldDefInput = CreateFieldDefInput

export function isRelationFieldDef(fieldDef: FieldDef): fieldDef is RelationFieldDef {
  return (
    fieldDef.dataType === 'relation' &&
    fieldDef.targetEntityTypeId !== undefined &&
    fieldDef.cardinality !== undefined
  )
}
