import type { EnumFieldId } from './ids'

export interface EnumField {
  id: EnumFieldId
  entityId: number
  fieldKey: string
  value: string
}

export interface EnumFieldList {
  items: EnumField[]
  limit: number
  offset: number
}

export interface CreateEnumFieldInput {
  entityId: number
  fieldKey: string
  value: string
}

export interface UpdateEnumFieldInput {
  fieldKey: string
  value: string
}
