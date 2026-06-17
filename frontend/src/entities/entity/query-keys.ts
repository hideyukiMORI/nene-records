import type { EntityId } from './ids'
import type { EntityStatus } from './model'

export type EntityRelationFilters = Record<string, number>

export type EntitySortKey = 'id' | 'published_at' | 'title'
export type EntitySortOrder = 'asc' | 'desc'

export interface EntityListParams {
  entityTypeId: number
  limit: number
  offset: number
  status?: EntityStatus
  tagSlugs?: string[]
  relationFilters?: EntityRelationFilters
  q?: string
  sortKey?: EntitySortKey
  sortOrder?: EntitySortOrder
  /** `'excerpt'` to request the server-computed teaser on each item. */
  include?: string
}

export const entityKeys = {
  all: ['entities'] as const,
  lists: () => [...entityKeys.all, 'list'] as const,
  list: (params: EntityListParams) => [...entityKeys.lists(), params] as const,
  latest: (limit: number) => [...entityKeys.all, 'latest', limit] as const,
  search: (q: string, limit: number) => [...entityKeys.all, 'search', q, limit] as const,
  byTag: (tagSlug: string, limit: number) => [...entityKeys.all, 'by-tag', tagSlug, limit] as const,
  byDateRange: (from: string, to: string, limit: number) =>
    [...entityKeys.all, 'by-date-range', from, to, limit] as const,
  details: () => [...entityKeys.all, 'detail'] as const,
  detail: (id: EntityId) => [...entityKeys.details(), id] as const,
  revisions: (id: EntityId) => [...entityKeys.detail(id), 'revisions'] as const,
}
