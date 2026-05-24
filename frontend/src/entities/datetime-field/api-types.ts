export interface DateTimeFieldDto {
  id: number
  entity_id: number
  field_key: string
  value: string
}

export interface DateTimeFieldListDto {
  items: DateTimeFieldDto[]
  limit: number
  offset: number
}

export interface CreateDateTimeFieldDto {
  entity_id: number
  field_key: string
  value: string
}

export interface UpdateDateTimeFieldDto {
  field_key: string
  value: string
}
