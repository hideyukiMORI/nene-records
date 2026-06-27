import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityDto, EntityListDto, EntityRevisionListDto } from './api-types'
import type { EntityId } from './ids'
import {
  mapEntityDtoToModel,
  mapEntityListDtoToModel,
  mapEntityRevisionListDtoToModel,
} from './mapper'
import type { Entity, EntityList, EntityRevisionList, EntityStatus } from './model'
import { entityKeys, type EntityListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 20, offset: 0 } as const

/** Append the shared list filters (status / tags / q / sort / relations / include). */
function appendEntityListFilters(search: URLSearchParams, params: EntityListParams): void {
  if (params.status !== undefined) {
    search.set('status', params.status)
  }
  if (params.tagSlugs !== undefined && params.tagSlugs.length > 0) {
    search.set('tags', params.tagSlugs.join(','))
  }
  if (params.q !== undefined && params.q !== '') {
    search.set('q', params.q)
  }
  if (params.sortKey !== undefined) {
    search.set('sort', params.sortKey)
  }
  if (params.sortOrder !== undefined) {
    search.set('order', params.sortOrder)
  }
  if (params.relationFilters !== undefined) {
    for (const [fieldKey, targetEntityId] of Object.entries(params.relationFilters)) {
      search.set(`relation.${fieldKey}`, String(targetEntityId))
    }
  }
  if (params.include !== undefined && params.include !== '') {
    search.set('include', params.include)
  }
}

export function useEntityList(
  params: EntityListParams,
  options?: { enabled?: boolean },
): UseQueryResult<EntityList, AppError> {
  return useQuery({
    queryKey: entityKeys.list(params),
    enabled: options?.enabled ?? true,
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
        entity_type_id: String(params.entityTypeId),
      })
      appendEntityListFilters(search, params)
      const dto = await apiClient.get<EntityListDto>(
        `/api/v1/entities?${search.toString()}`,
        signal,
      )
      return mapEntityListDtoToModel(dto)
    },
  })
}

/** Per-request page size for the directory fetch — the public list endpoint's cap. */
const DIRECTORY_PAGE_SIZE = 100
/** Safety bound on how many permalink records the directory tree will load (#682). */
export const DIRECTORY_MAX_RECORDS = 5000

export interface DirectoryEntityList {
  items: Entity[]
  total: number
  truncated: boolean
}

/**
 * Fetches ALL permalink-bearing records for the admin directory tree by paging
 * through the public list endpoint {@link DIRECTORY_PAGE_SIZE} at a time (its
 * per-request cap), up to {@link DIRECTORY_MAX_RECORDS}. Each request stays small
 * (public-safe) while the tree stays complete — no silent first-100 cut-off (#682).
 */
export function useDirectoryEntityList(
  params: EntityListParams,
  options?: { enabled?: boolean },
): UseQueryResult<DirectoryEntityList, AppError> {
  return useQuery({
    queryKey: [...entityKeys.list(params), 'directory'],
    enabled: options?.enabled ?? true,
    queryFn: async ({ signal }) => {
      const items: Entity[] = []
      let offset = 0
      // Assigned on the first (always-run) pass before the while-condition reads it.
      let total: number
      do {
        const search = new URLSearchParams({
          limit: String(DIRECTORY_PAGE_SIZE),
          offset: String(offset),
          entity_type_id: String(params.entityTypeId),
          has_permalink: '1',
        })
        appendEntityListFilters(search, params)
        search.set('include', 'views')
        const dto = await apiClient.get<EntityListDto>(
          `/api/v1/entities?${search.toString()}`,
          signal,
        )
        const page = mapEntityListDtoToModel(dto)
        items.push(...page.items)
        total = page.total
        offset += DIRECTORY_PAGE_SIZE
      } while (offset < total && items.length < DIRECTORY_MAX_RECORDS)

      return {
        items: items.slice(0, DIRECTORY_MAX_RECORDS),
        total,
        truncated: total > DIRECTORY_MAX_RECORDS,
      }
    },
  })
}

/**
 * Latest published entities across every type, newest first. Omits
 * entity_type_id so the backend spans all types (like useEntitySearch) and
 * sorts by published_at desc. Powers the public home "Latest records" feed.
 */
export function usePublicLatestEntities(options?: {
  limit?: number
  enabled?: boolean
}): UseQueryResult<EntityList, AppError> {
  const limit = options?.limit ?? 13
  return useQuery({
    queryKey: entityKeys.latest(limit),
    enabled: options?.enabled ?? true,
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(limit),
        offset: '0',
        status: 'published',
        sort: 'published_at',
        order: 'desc',
        include: 'excerpt',
      })
      const dto = await apiClient.get<EntityListDto>(
        `/api/v1/entities?${search.toString()}`,
        signal,
      )
      return mapEntityListDtoToModel(dto)
    },
  })
}

/**
 * Site-wide full-text search across published entities of every type. Unlike
 * useEntityList, it omits entity_type_id so the backend searches all types
 * (a sent entity_type_id=0 would match nothing). Disabled for an empty query.
 */
export function useEntitySearch(
  q: string,
  options?: { limit?: number; enabled?: boolean },
): UseQueryResult<EntityList, AppError> {
  const limit = options?.limit ?? 30
  return useQuery({
    queryKey: entityKeys.search(q, limit),
    enabled: (options?.enabled ?? true) && q !== '',
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(limit),
        offset: '0',
        status: 'published',
        q,
      })
      const dto = await apiClient.get<EntityListDto>(
        `/api/v1/entities?${search.toString()}`,
        signal,
      )
      return mapEntityListDtoToModel(dto)
    },
  })
}

/**
 * Published entities of every type carrying a given tag slug. Like useEntitySearch
 * it omits entity_type_id so the backend matches across all types. Disabled for
 * an empty slug.
 */
export function useEntitiesByTag(
  tagSlug: string,
  options?: { limit?: number; enabled?: boolean },
): UseQueryResult<EntityList, AppError> {
  const limit = options?.limit ?? 50
  return useQuery({
    queryKey: entityKeys.byTag(tagSlug, limit),
    enabled: (options?.enabled ?? true) && tagSlug !== '',
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(limit),
        offset: '0',
        status: 'published',
        tags: tagSlug,
      })
      const dto = await apiClient.get<EntityListDto>(
        `/api/v1/entities?${search.toString()}`,
        signal,
      )
      return mapEntityListDtoToModel(dto)
    },
  })
}

/**
 * Published entities of every type published within [from, to] (inclusive,
 * YYYY-MM-DD). Omits entity_type_id to search across types. Disabled until both
 * dates are set.
 */
export function useEntitiesByDateRange(
  from: string,
  to: string,
  options?: { limit?: number; enabled?: boolean },
): UseQueryResult<EntityList, AppError> {
  const limit = options?.limit ?? 100
  return useQuery({
    queryKey: entityKeys.byDateRange(from, to, limit),
    enabled: (options?.enabled ?? true) && from !== '' && to !== '',
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(limit),
        offset: '0',
        status: 'published',
        published_from: from,
        published_to: to,
      })
      const dto = await apiClient.get<EntityListDto>(
        `/api/v1/entities?${search.toString()}`,
        signal,
      )
      return mapEntityListDtoToModel(dto)
    },
  })
}

export function useEntity(
  id: EntityId,
  options?: { enabled?: boolean },
): UseQueryResult<Entity, AppError> {
  return useQuery({
    queryKey: entityKeys.detail(id),
    enabled: options?.enabled ?? true,
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<EntityDto>(`/api/v1/entities/${String(id)}`, signal)
      return mapEntityDtoToModel(dto)
    },
  })
}

const DEFAULT_REVISION_PARAMS = { limit: 20, offset: 0 } as const

export function useEntityRevisions(
  id: EntityId,
  params: { limit: number; offset: number } = DEFAULT_REVISION_PARAMS,
): UseQueryResult<EntityRevisionList, AppError> {
  return useQuery({
    queryKey: [...entityKeys.revisions(id), params],
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      const dto = await apiClient.get<EntityRevisionListDto>(
        `/api/v1/entities/${String(id)}/revisions?${search.toString()}`,
        signal,
      )
      return mapEntityRevisionListDtoToModel(dto)
    },
    enabled: id > 0,
  })
}

export function defaultEntityListParams(
  entityTypeId: number,
  tagSlugs: string[] = [],
  relationFilters: EntityListParams['relationFilters'] = {},
  offset = 0,
  q?: string,
  status?: EntityStatus,
  sortKey?: EntityListParams['sortKey'],
  sortOrder?: EntityListParams['sortOrder'],
): EntityListParams {
  return {
    entityTypeId,
    tagSlugs,
    relationFilters,
    limit: DEFAULT_LIST_PARAMS.limit,
    offset,
    ...(q !== undefined && q !== '' ? { q } : {}),
    ...(status !== undefined ? { status } : {}),
    ...(sortKey !== undefined ? { sortKey } : {}),
    ...(sortOrder !== undefined ? { sortOrder } : {}),
  }
}
