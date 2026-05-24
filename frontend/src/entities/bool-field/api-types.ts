export interface BoolFieldDto {
  id: number
  entity_id: number
  field_key: string
  value: boolean
}

export interface BoolFieldListDto {
  items: BoolFieldDto[]
  limit: number
  offset: number
}

export interface CreateBoolFieldDto {
  entity_id: number
  field_key: string
  value: boolean
}

export interface UpdateBoolFieldDto {
  field_key: string
  value: boolean
}
