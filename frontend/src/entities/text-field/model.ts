import type { TextFieldId } from './ids'

export interface TextField {
  id: TextFieldId
  entityId: number
  fieldKey: string
  value: string
}

export interface TextFieldList {
  items: TextField[]
  limit: number
  offset: number
}

export interface CreateTextFieldInput {
  entityId: number
  fieldKey: string
  value: string
}

export interface UpdateTextFieldInput {
  fieldKey: string
  value: string
}
