import type { PublicLayoutKey } from '@/shared/lib/resolve-layout'
import type { EntityId } from './ids'

export type EntityStatus = 'draft' | 'published' | 'archived' | 'scheduled'

export interface Entity {
  id: EntityId
  entityTypeId: number
  slug: string | null
  /** Custom canonical URL path overriding the type pattern; null = use the pattern. */
  permalink: string | null
  /** Per-entity layout override; null = inherit the type's default layout. */
  layout: PublicLayoutKey | null
  /** Comments visibility on the public page; null = follow record_page_config (#775). */
  showComments: boolean | null
  /** Related-records visibility on the public page; null = follow record_page_config (#775). */
  showRelated: boolean | null
  status: EntityStatus
  publishedAt: string | null
  scheduledAt: string | null
  isDeleted: boolean
  deletedAt: string | null
  metaTitle: string | null
  metaDescription: string | null
  /** Manual sibling order in the directory / public nav, lower first (#659). */
  menuOrder: number
  /** Server-computed teaser; present only when listed with `include=excerpt`. */
  excerpt?: string | null
  /** View count over the last 30 days; present only when listed with `include=views` (#674). */
  viewCount?: number
  createdAt: string | null
  updatedAt: string | null
}

export interface EntityList {
  items: Entity[]
  limit: number
  offset: number
  total: number
}

export interface CreateEntityInput {
  entityTypeId: number
  slug?: string | null
  permalink?: string | null
  status?: EntityStatus
  layout?: PublicLayoutKey | null
  showComments?: boolean | null
  showRelated?: boolean | null
}

export interface UpdateEntityInput {
  id: number
  entityTypeId: number
  slug?: string | null
  permalink?: string | null
  status: EntityStatus
  publishedAt?: string | null
  scheduledAt?: string | null
  metaTitle?: string | null
  metaDescription?: string | null
  layout?: PublicLayoutKey | null
  showComments?: boolean | null
  showRelated?: boolean | null
}

export interface ScheduleEntityInput {
  id: number
  scheduledAt: string
}

export interface ScheduleEntityOutput {
  id: number
  status: EntityStatus
  scheduledAt: string
}

export interface GeneratePreviewTokenInput {
  id: number
}

export interface GeneratePreviewTokenOutput {
  token: string
  expiresAt: string
  previewUrl: string
}

export interface RevokePreviewTokenInput {
  id: number
}

export type EntityRevisionAction = 'created' | 'updated' | 'deleted' | 'restored'

export interface EntityRevision {
  id: number
  entityId: number
  action: EntityRevisionAction
  status: string
  previousStatus: string | null
  slug: string | null
  previousSlug: string | null
  actorUserId: number | null
  createdAt: string
}

export interface EntityRevisionList {
  items: EntityRevision[]
  limit: number
  offset: number
}
