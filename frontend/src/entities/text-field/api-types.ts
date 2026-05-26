export interface TextFieldDto {
  id: number
  entity_id: number
  field_key: string
  value: string
  locale: string | null
}

export interface TextFieldListDto {
  items: TextFieldDto[]
  limit: number
  offset: number
}

export interface CreateTextFieldDto {
  entity_id: number
  field_key: string
  value: string
  locale?: string | null
}

export interface UpdateTextFieldDto {
  field_key: string
  value: string
  locale?: string | null
}
