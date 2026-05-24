import type { IntFieldId } from './ids'

export interface IntField {
  id: IntFieldId
  entityId: number
  fieldKey: string
  value: number
}

export interface IntFieldList {
  items: IntField[]
  limit: number
  offset: number
}

export interface CreateIntFieldInput {
  entityId: number
  fieldKey: string
  value: number
}

export interface UpdateIntFieldInput {
  fieldKey: string
  value: number
}
