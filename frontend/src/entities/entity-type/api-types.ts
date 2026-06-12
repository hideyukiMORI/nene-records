export interface EntityTypeDto {
  id: number
  name: string
  slug: string
  is_pinned: boolean
  /** Sidebar / pinned ordering (ascending). May be absent on older payloads. */
  display_order?: number
  /** Locale-keyed display names, e.g. {"ja":"投稿","fr":"Articles"}. Empty object = no overrides. */
  labels?: Record<string, string>
  /**
   * URL pattern for public records.
   * Tokens: {type} {slug} {id} {year} {month} {day}
   * Null = default "/{type}/{id}".
   */
  permalink_pattern?: string | null
  /** Previous URL pattern, saved automatically when permalink_pattern changes. */
  previous_permalink_pattern?: string | null
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
  permalink_pattern?: string | null
}
