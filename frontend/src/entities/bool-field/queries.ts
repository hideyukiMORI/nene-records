import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { BoolFieldDto, BoolFieldListDto } from './api-types'
import type { BoolFieldId } from './ids'
import { mapBoolFieldDtoToModel, mapBoolFieldListDtoToModel } from './mapper'
import type { BoolField, BoolFieldList } from './model'
import { boolFieldKeys, type BoolFieldListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 100, offset: 0 } as const

export function useBoolFieldList(
  params: BoolFieldListParams = DEFAULT_LIST_PARAMS,
): UseQueryResult<BoolFieldList, AppError> {
  return useQuery({
    queryKey: boolFieldKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      if (params.entityId !== undefined) {
        search.set('entity_id', String(params.entityId))
      }
      const dto = await apiClient.get<BoolFieldListDto>(
        `/api/v1/bool-fields?${search.toString()}`,
        signal,
      )
      return mapBoolFieldListDtoToModel(dto)
    },
  })
}

export function useBoolField(id: BoolFieldId): UseQueryResult<BoolField, AppError> {
  return useQuery({
    queryKey: boolFieldKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<BoolFieldDto>(`/api/v1/bool-fields/${String(id)}`, signal)
      return mapBoolFieldDtoToModel(dto)
    },
  })
}
