export interface EntityDto {
  id: number
  entity_type_id: number
  is_deleted: boolean
  deleted_at: string | null
}

export interface EntityListDto {
  items: EntityDto[]
  limit: number
  offset: number
  total: number
}

export interface CreateEntityDto {
  entity_type_id: number
}
