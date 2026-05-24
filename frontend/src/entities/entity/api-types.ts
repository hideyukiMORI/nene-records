export type EntityStatusDto = 'draft' | 'published' | 'archived'

export interface EntityDto {
  id: number
  entity_type_id: number
  status: EntityStatusDto
  published_at: string | null
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
  status?: EntityStatusDto
}

export interface UpdateEntityDto {
  entity_type_id: number
  status: EntityStatusDto
  published_at?: string | null
}
