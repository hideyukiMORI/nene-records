import type { BoolFieldId } from './ids'

export interface BoolField {
  id: BoolFieldId
  entityId: number
  fieldKey: string
  value: boolean
}

export interface BoolFieldList {
  items: BoolField[]
  limit: number
  offset: number
}

export interface CreateBoolFieldInput {
  entityId: number
  fieldKey: string
  value: boolean
}

export interface UpdateBoolFieldInput {
  fieldKey: string
  value: boolean
}
