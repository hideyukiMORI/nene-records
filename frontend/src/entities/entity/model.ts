import type { EntityId } from './ids'

export type EntityStatus = 'draft' | 'published' | 'archived'

export interface Entity {
  id: EntityId
  entityTypeId: number
  slug: string | null
  status: EntityStatus
  publishedAt: string | null
  isDeleted: boolean
  deletedAt: string | null
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
  status?: EntityStatus
}

export interface UpdateEntityInput {
  id: number
  entityTypeId: number
  slug?: string | null
  status: EntityStatus
  publishedAt?: string | null
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
