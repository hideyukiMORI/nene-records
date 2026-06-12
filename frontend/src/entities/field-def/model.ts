import type { ContentRegion } from '@/shared/lib/resolve-layout'
import type { FieldDataType, RelationCardinality } from './enum'
import type { FieldDefId } from './ids'

export interface FieldDef {
  id: FieldDefId
  entityTypeId: number
  fieldKey: string
  dataType: FieldDataType
  targetEntityTypeId?: number
  cardinality?: RelationCardinality
  /** Layout region this field renders into; null = main. */
  region: ContentRegion | null
  /** Ascending display order within the entity type. */
  displayOrder: number
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
  region?: ContentRegion | null
  displayOrder?: number
}

export type UpdateFieldDefInput = CreateFieldDefInput

export function isRelationFieldDef(fieldDef: FieldDef): fieldDef is RelationFieldDef {
  return (
    fieldDef.dataType === 'relation' &&
    fieldDef.targetEntityTypeId !== undefined &&
    fieldDef.cardinality !== undefined
  )
}
