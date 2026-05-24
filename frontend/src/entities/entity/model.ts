import type { EntityId } from './ids'

export type EntityStatus = 'draft' | 'published' | 'archived'

export interface Entity {
  id: EntityId
  entityTypeId: number
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
  status?: EntityStatus
}

export interface UpdateEntityInput {
  id: number
  entityTypeId: number
  status: EntityStatus
  publishedAt?: string | null
}
