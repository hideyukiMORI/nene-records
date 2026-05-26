import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityDto, EntityListDto, EntityRevisionListDto } from './api-types'
import type { EntityId } from './ids'
import {
  mapEntityDtoToModel,
  mapEntityListDtoToModel,
  mapEntityRevisionListDtoToModel,
} from './mapper'
import type { Entity, EntityList, EntityRevisionList } from './model'
import { entityKeys, type EntityListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 20, offset: 0 } as const

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
      if (params.status !== undefined) {
        search.set('status', params.status)
      }
      if (params.tagSlugs !== undefined && params.tagSlugs.length > 0) {
        search.set('tags', params.tagSlugs.join(','))
      }
      if (params.q !== undefined && params.q !== '') {
        search.set('q', params.q)
      }
      if (params.relationFilters !== undefined) {
        for (const [fieldKey, targetEntityId] of Object.entries(params.relationFilters)) {
          search.set(`relation.${fieldKey}`, String(targetEntityId))
        }
      }
      const dto = await apiClient.get<EntityListDto>(
        `/api/v1/entities?${search.toString()}`,
        signal,
      )
      return mapEntityListDtoToModel(dto)
    },
  })
}

export function useEntity(id: EntityId): UseQueryResult<Entity, AppError> {
  return useQuery({
    queryKey: entityKeys.detail(id),
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
): EntityListParams {
  return {
    entityTypeId,
    tagSlugs,
    relationFilters,
    limit: DEFAULT_LIST_PARAMS.limit,
    offset,
    q: q !== '' ? q : undefined,
  }
}
