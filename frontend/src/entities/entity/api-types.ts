export type EntityStatusDto = 'draft' | 'published' | 'archived' | 'scheduled'

export interface EntityDto {
  id: number
  entity_type_id: number
  slug: string | null
  status: EntityStatusDto
  published_at: string | null
  scheduled_at: string | null
  is_deleted: boolean
  deleted_at: string | null
  meta_title: string | null
  meta_description: string | null
}

export interface EntityListDto {
  items: EntityDto[]
  limit: number
  offset: number
  total: number
}

export interface CreateEntityDto {
  entity_type_id: number
  slug?: string | null
  status?: EntityStatusDto
}

export interface UpdateEntityDto {
  entity_type_id: number
  slug?: string | null
  status: EntityStatusDto
  published_at?: string | null
  scheduled_at?: string | null
  meta_title?: string | null
  meta_description?: string | null
}

export interface ScheduleEntityDto {
  scheduled_at: string
}

export interface ScheduleEntityResponseDto {
  id: number
  status: EntityStatusDto
  scheduled_at: string
}

export interface GeneratePreviewTokenResponseDto {
  token: string
  expires_at: string
  preview_url: string
}

export interface EntityRevisionDto {
  id: number
  entity_id: number
  action: 'created' | 'updated' | 'deleted' | 'restored'
  status: string
  previous_status: string | null
  slug: string | null
  previous_slug: string | null
  actor_user_id: number | null
  created_at: string
}

export interface EntityRevisionListDto {
  items: EntityRevisionDto[]
  limit: number
  offset: number
}
