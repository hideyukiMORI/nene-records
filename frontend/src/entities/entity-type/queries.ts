import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityTypeListDto } from './api-types'
import type { EntityTypeId } from './ids'
import type { EntityTypeDto } from './api-types'
import { mapEntityTypeDtoToModel, mapEntityTypeListDtoToModel } from './mapper'
import type { EntityType, EntityTypeList } from './model'
import { entityTypeKeys } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 20, offset: 0 } as const
const PINNED_LIST_PARAMS = { limit: 100, offset: 0 } as const

export function useEntityTypeList(
  params: { limit: number; offset: number } = DEFAULT_LIST_PARAMS,
): UseQueryResult<EntityTypeList, AppError> {
  return useQuery({
    queryKey: entityTypeKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      const dto = await apiClient.get<EntityTypeListDto>(
        `/api/v1/entity-types?${search.toString()}`,
        signal,
      )
      return mapEntityTypeListDtoToModel(dto)
    },
  })
}

export function usePinnedEntityTypes(): UseQueryResult<EntityType[], AppError> {
  return useQuery({
    queryKey: [...entityTypeKeys.list(PINNED_LIST_PARAMS), 'pinned'],
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(PINNED_LIST_PARAMS.limit),
        offset: String(PINNED_LIST_PARAMS.offset),
      })
      const dto = await apiClient.get<EntityTypeListDto>(
        `/api/v1/entity-types?${search.toString()}`,
        signal,
      )
      const list = mapEntityTypeListDtoToModel(dto)
      return list.items.filter((item) => item.isPinned)
    },
    staleTime: 5 * 60 * 1000, // 5 minutes — sidebar nav doesn't need frequent refresh
  })
}

export function useEntityType(id: EntityTypeId): UseQueryResult<EntityType, AppError> {
  return useQuery({
    queryKey: entityTypeKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<EntityTypeDto>(`/api/v1/entity-types/${String(id)}`, signal)
      return mapEntityTypeDtoToModel(dto)
    },
  })
}

const SLUG_LOOKUP_PARAMS = { limit: 100, offset: 0 } as const

/**
 * Looks up an entity type by its slug.
 * Uses the same list endpoint as usePinnedEntityTypes (cache-friendly).
 *
 * NOTE: Slug resolution is done client-side over the first {@link SLUG_LOOKUP_PARAMS}.limit
 * (100) entity types. If an organization ever holds more than 100 entity types, slugs on the
 * 101st+ type will not be found here and surface as a misleading 404 ("No entity type with
 * slug ..."). This is intentional for now (cache-friendly, well under realistic scale). When
 * that ceiling becomes a concern, switch to a server-side slug filter
 * (`GET /api/v1/entity-types?slug=xxx`) instead of raising the client-side limit. See #278.
 */
export function useEntityTypeBySlug(slug: string): UseQueryResult<EntityType, AppError> {
  return useQuery({
    queryKey: [...entityTypeKeys.list(SLUG_LOOKUP_PARAMS), 'bySlug', slug],
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(SLUG_LOOKUP_PARAMS.limit),
        offset: String(SLUG_LOOKUP_PARAMS.offset),
      })
      const dto = await apiClient.get<EntityTypeListDto>(
        `/api/v1/entity-types?${search.toString()}`,
        signal,
      )
      const list = mapEntityTypeListDtoToModel(dto)
      const found = list.items.find((item) => item.slug === slug)
      if (found === undefined) {
        throw new AppError({
          type: 'about:blank',
          title: 'Not Found',
          status: 404,
          instance: `/api/v1/entity-types?slug=${slug}`,
          detail: `No entity type with slug "${slug}"`,
        })
      }
      return found
    },
    enabled: slug.length > 0,
  })
}
