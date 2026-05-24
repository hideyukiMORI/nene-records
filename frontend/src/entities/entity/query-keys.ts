import type { EntityId } from './ids'

export type EntityRelationFilters = Record<string, number>

export interface EntityListParams {
  entityTypeId: number
  limit: number
  offset: number
  tagSlugs?: string[]
  relationFilters?: EntityRelationFilters
}

export const entityKeys = {
  all: ['entities'] as const,
  lists: () => [...entityKeys.all, 'list'] as const,
  list: (params: EntityListParams) => [...entityKeys.lists(), params] as const,
  details: () => [...entityKeys.all, 'detail'] as const,
  detail: (id: EntityId) => [...entityKeys.details(), id] as const,
}
