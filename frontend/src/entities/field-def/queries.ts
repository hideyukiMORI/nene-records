import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { FieldDefDto, FieldDefListDto } from './api-types'
import type { FieldDefId } from './ids'
import { mapFieldDefDtoToModel, mapFieldDefListDtoToModel } from './mapper'
import type { FieldDef, FieldDefList } from './model'
import { fieldDefKeys, type FieldDefListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 20, offset: 0 } as const

export function useFieldDefList(
  params: FieldDefListParams,
): UseQueryResult<FieldDefList, AppError> {
  return useQuery({
    queryKey: fieldDefKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
        entity_type_id: String(params.entityTypeId),
      })
      const dto = await apiClient.get<FieldDefListDto>(
        `/api/v1/field-defs?${search.toString()}`,
        signal,
      )
      return mapFieldDefListDtoToModel(dto)
    },
  })
}

export function useFieldDef(id: FieldDefId): UseQueryResult<FieldDef, AppError> {
  return useQuery({
    queryKey: fieldDefKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<FieldDefDto>(`/api/v1/field-defs/${String(id)}`, signal)
      return mapFieldDefDtoToModel(dto)
    },
  })
}

export function defaultFieldDefListParams(entityTypeId: number): FieldDefListParams {
  return {
    entityTypeId,
    ...DEFAULT_LIST_PARAMS,
  }
}
