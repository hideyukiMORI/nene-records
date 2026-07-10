export type EntityStatusDto = 'draft' | 'published' | 'archived' | 'scheduled'

export type LayoutDto = 'standard' | 'full' | 'two-col' | 'three-col' | 'bare' | 'custom'

export interface EntityDto {
  id: number
  entity_type_id: number
  slug: string | null
  /** Custom canonical URL path overriding the type pattern; null = use the pattern. */
  permalink: string | null
  layout: LayoutDto | null
  /** Per-record comments visibility; null = follow record_page_config (#775). */
  show_comments?: boolean | null
  /** Per-record related-records visibility; null = follow record_page_config (#775). */
  show_related?: boolean | null
  status: EntityStatusDto
  published_at: string | null
  scheduled_at: string | null
  is_deleted: boolean
  deleted_at: string | null
  meta_title: string | null
  meta_description: string | null
  /** Manual sibling order in the directory / public nav, lower first (#659). */
  menu_order?: number | null
  /** Server-computed teaser; present only when listed with `?include=excerpt`. */
  excerpt?: string | null
  /** View count over the last 30 days; present only when listed with `?include=views` (#674). */
  view_count?: number | null
  created_at: string | null
  updated_at: string | null
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
  permalink?: string | null
  status?: EntityStatusDto
  layout?: LayoutDto | null
  show_comments?: boolean | null
  show_related?: boolean | null
}

export interface UpdateEntityDto {
  entity_type_id: number
  slug?: string | null
  permalink?: string | null
  status: EntityStatusDto
  published_at?: string | null
  scheduled_at?: string | null
  meta_title?: string | null
  meta_description?: string | null
  layout?: LayoutDto | null
  show_comments?: boolean | null
  show_related?: boolean | null
}

export interface ScheduleEntityDto {
  scheduled_at: string
}

export interface ScheduleEntityResponseDto {
  id: number
  status: EntityStatusDto
  scheduled_at: string
}

export interface EntityMoveResponseDto {
  id: number
  permalink: string
  moved_count: number
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
