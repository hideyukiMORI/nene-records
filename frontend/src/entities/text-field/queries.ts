import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { TextFieldDto, TextFieldListDto } from './api-types'
import type { TextFieldId } from './ids'
import { mapTextFieldDtoToModel, mapTextFieldListDtoToModel } from './mapper'
import type { TextField, TextFieldList } from './model'
import { textFieldKeys, type TextFieldListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 100, offset: 0 } as const

export function useTextFieldList(
  params: TextFieldListParams = DEFAULT_LIST_PARAMS,
): UseQueryResult<TextFieldList, AppError> {
  return useQuery({
    queryKey: textFieldKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      const dto = await apiClient.get<TextFieldListDto>(
        `/api/v1/text-fields?${search.toString()}`,
        signal,
      )
      return mapTextFieldListDtoToModel(dto)
    },
  })
}

export function useTextField(id: TextFieldId): UseQueryResult<TextField, AppError> {
  return useQuery({
    queryKey: textFieldKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<TextFieldDto>(`/api/v1/text-fields/${String(id)}`, signal)
      return mapTextFieldDtoToModel(dto)
    },
  })
}
