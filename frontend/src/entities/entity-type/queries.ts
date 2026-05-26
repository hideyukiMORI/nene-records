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
