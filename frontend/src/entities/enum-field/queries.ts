import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EnumFieldDto, EnumFieldListDto } from './api-types'
import type { EnumFieldId } from './ids'
import { mapEnumFieldDtoToModel, mapEnumFieldListDtoToModel } from './mapper'
import type { EnumField, EnumFieldList } from './model'
import { enumFieldKeys, type EnumFieldListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 100, offset: 0 } as const

export function useEnumFieldList(
  params: EnumFieldListParams = DEFAULT_LIST_PARAMS,
): UseQueryResult<EnumFieldList, AppError> {
  return useQuery({
    queryKey: enumFieldKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      if (params.entityId !== undefined) {
        search.set('entity_id', String(params.entityId))
      }
      const dto = await apiClient.get<EnumFieldListDto>(
        `/api/v1/enum-fields?${search.toString()}`,
        signal,
      )
      return mapEnumFieldListDtoToModel(dto)
    },
  })
}

export function useEnumField(id: EnumFieldId): UseQueryResult<EnumField, AppError> {
  return useQuery({
    queryKey: enumFieldKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<EnumFieldDto>(`/api/v1/enum-fields/${String(id)}`, signal)
      return mapEnumFieldDtoToModel(dto)
    },
  })
}
