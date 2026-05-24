import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { IntFieldDto, IntFieldListDto } from './api-types'
import type { IntFieldId } from './ids'
import { mapIntFieldDtoToModel, mapIntFieldListDtoToModel } from './mapper'
import type { IntField, IntFieldList } from './model'
import { intFieldKeys, type IntFieldListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 100, offset: 0 } as const

export function useIntFieldList(
  params: IntFieldListParams = DEFAULT_LIST_PARAMS,
): UseQueryResult<IntFieldList, AppError> {
  return useQuery({
    queryKey: intFieldKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      if (params.entityId !== undefined) {
        search.set('entity_id', String(params.entityId))
      }
      const dto = await apiClient.get<IntFieldListDto>(
        `/api/v1/int-fields?${search.toString()}`,
        signal,
      )
      return mapIntFieldListDtoToModel(dto)
    },
  })
}

export function useIntField(id: IntFieldId): UseQueryResult<IntField, AppError> {
  return useQuery({
    queryKey: intFieldKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<IntFieldDto>(`/api/v1/int-fields/${String(id)}`, signal)
      return mapIntFieldDtoToModel(dto)
    },
  })
}
