export interface EnumFieldDto {
  id: number
  entity_id: number
  field_key: string
  value: string
}

export interface EnumFieldListDto {
  items: EnumFieldDto[]
  limit: number
  offset: number
}

export interface CreateEnumFieldDto {
  entity_id: number
  field_key: string
  value: string
}

export interface UpdateEnumFieldDto {
  field_key: string
  value: string
}
