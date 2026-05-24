import type { EntityId } from './ids'

export interface Entity {
  id: EntityId
  entityTypeId: number
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
}
