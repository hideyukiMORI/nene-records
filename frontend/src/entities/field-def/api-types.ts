import type { ContentRegion } from '@/shared/lib/resolve-layout'
import type { FieldDataType, RelationCardinality } from './enum'

export interface FieldDefDto {
  id: number
  entity_type_id: number
  field_key: string
  data_type: FieldDataType
  target_entity_type_id?: number
  cardinality?: RelationCardinality
  region?: ContentRegion | null
  display_order?: number
}

export interface FieldDefListDto {
  items: FieldDefDto[]
  limit: number
  offset: number
}

export interface CreateFieldDefDto {
  entity_type_id: number
  field_key: string
  data_type: FieldDataType
  target_entity_type_id?: number
  cardinality?: RelationCardinality
  region?: ContentRegion | null
  display_order?: number
}

export type UpdateFieldDefDto = CreateFieldDefDto
