import type { FieldDataType } from './enum'

export interface FieldDefDto {
  id: number
  entity_type_id: number
  field_key: string
  data_type: FieldDataType
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
}

export type UpdateFieldDefDto = CreateFieldDefDto
