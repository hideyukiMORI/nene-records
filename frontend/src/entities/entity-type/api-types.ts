export interface EntityTypeDto {
  id: number
  name: string
  slug: string
  is_pinned: boolean
  /** Locale-keyed display names, e.g. {"ja":"投稿","fr":"Articles"}. Empty object = no overrides. */
  labels?: Record<string, string>
}

export interface EntityTypeListDto {
  items: EntityTypeDto[]
  limit: number
  offset: number
}

export interface CreateEntityTypeDto {
  name: string
  slug: string
  is_pinned?: boolean
}

export interface UpdateEntityTypeDto {
  name: string
  slug: string
  is_pinned?: boolean
  labels?: Record<string, string>
}
