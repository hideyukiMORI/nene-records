import type { EntityTypeId } from './ids'

export interface EntityType {
  id: EntityTypeId
  name: string
  slug: string
  isPinned: boolean
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

export type UpdateEntityTypeInput = CreateEntityTypeInput
