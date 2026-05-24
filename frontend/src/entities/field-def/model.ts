import type { FieldDataType } from './enum'
import type { FieldDefId } from './ids'

export interface FieldDef {
  id: FieldDefId
  entityTypeId: number
  fieldKey: string
  dataType: FieldDataType
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
}

export type UpdateFieldDefInput = CreateFieldDefInput
