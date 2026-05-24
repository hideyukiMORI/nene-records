import type { DateTimeFieldId } from './ids'

export interface DateTimeField {
  id: DateTimeFieldId
  entityId: number
  fieldKey: string
  value: string
}

export interface DateTimeFieldList {
  items: DateTimeField[]
  limit: number
  offset: number
}

export interface CreateDateTimeFieldInput {
  entityId: number
  fieldKey: string
  value: string
}

export interface UpdateDateTimeFieldInput {
  fieldKey: string
  value: string
}
