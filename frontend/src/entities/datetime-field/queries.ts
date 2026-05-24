import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DateTimeFieldDto, DateTimeFieldListDto } from './api-types'
import type { DateTimeFieldId } from './ids'
import { mapDateTimeFieldDtoToModel, mapDateTimeFieldListDtoToModel } from './mapper'
import type { DateTimeField, DateTimeFieldList } from './model'
import { dateTimeFieldKeys, type DateTimeFieldListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 100, offset: 0 } as const

export function useDateTimeFieldList(
  params: DateTimeFieldListParams = DEFAULT_LIST_PARAMS,
): UseQueryResult<DateTimeFieldList, AppError> {
  return useQuery({
    queryKey: dateTimeFieldKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      if (params.entityId !== undefined) {
        search.set('entity_id', String(params.entityId))
      }
      const dto = await apiClient.get<DateTimeFieldListDto>(
        `/api/v1/datetime-fields?${search.toString()}`,
        signal,
      )
      return mapDateTimeFieldListDtoToModel(dto)
    },
  })
}

export function useDateTimeField(id: DateTimeFieldId): UseQueryResult<DateTimeField, AppError> {
  return useQuery({
    queryKey: dateTimeFieldKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<DateTimeFieldDto>(
        `/api/v1/datetime-fields/${String(id)}`,
        signal,
      )
      return mapDateTimeFieldDtoToModel(dto)
    },
  })
}
