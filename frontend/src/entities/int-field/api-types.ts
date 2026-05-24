export interface IntFieldDto {
  id: number
  entity_id: number
  field_key: string
  value: number
}

export interface IntFieldListDto {
  items: IntFieldDto[]
  limit: number
  offset: number
}

export interface CreateIntFieldDto {
  entity_id: number
  field_key: string
  value: number
}

export interface UpdateIntFieldDto {
  field_key: string
  value: number
}
