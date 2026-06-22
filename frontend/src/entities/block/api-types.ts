export interface BlocksFieldDto {
  id: number
  entity_id: number
  field_key: string
  value: string
  locale: string | null
}

export interface BlocksFieldListDto {
  items: BlocksFieldDto[]
  limit: number
  offset: number
}

export interface CreateBlocksFieldDto {
  entity_id: number
  field_key: string
  value: string
  locale?: string | null
}

export interface UpdateBlocksFieldDto {
  field_key: string
  value: string
  locale?: string | null
}
