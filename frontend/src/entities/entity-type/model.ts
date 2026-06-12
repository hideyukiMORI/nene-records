import type { PublicLayoutKey } from '@/shared/lib/resolve-layout'
import type { EntityTypeId } from './ids'

export interface EntityType {
  id: EntityTypeId
  name: string
  slug: string
  isPinned: boolean
  /** Default public-page layout for records of this type. Overridable per entity. */
  defaultLayout: PublicLayoutKey
  /** Sidebar / pinned ordering (ascending). Lower appears first. */
  displayOrder: number
  /** Locale-keyed display names. Empty object / undefined = no overrides. */
  labels?: Record<string, string>
  /**
   * URL pattern for public records.
   * Tokens: {type} {slug} {id} {year} {month} {day}
   * Undefined/null = use default "/{type}/{id}".
   */
  permalinkPattern?: string | null
  /** Previous URL pattern — saved when permalink_pattern changes. Used to redirect old URLs. */
  previousPermalinkPattern?: string | null
}

export interface EntityTypeList {
  items: EntityType[]
  limit: number
  offset: number
}

export interface CreateEntityTypeInput {
  name: string
  slug: string
  isPinned?: boolean
}

export interface UpdateEntityTypeInput {
  name: string
  slug: string
  isPinned?: boolean
  labels?: Record<string, string>
  permalinkPattern?: string | null
  defaultLayout?: PublicLayoutKey
}
