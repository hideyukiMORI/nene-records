import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { BoolFieldDto } from './api-types'
import type { BoolFieldId } from './ids'
import { mapBoolFieldDtoToModel, mapCreateInputToDto, mapUpdateInputToDto } from './mapper'
import type { BoolField, CreateBoolFieldInput, UpdateBoolFieldInput } from './model'
import { boolFieldKeys } from './query-keys'

export function useCreateBoolField(): UseMutationResult<BoolField, AppError, CreateBoolFieldInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<BoolFieldDto>(
        '/api/v1/bool-fields',
        mapCreateInputToDto(input),
      )
      return mapBoolFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: boolFieldKeys.lists() })
    },
  })
}

export function useUpdateBoolField(): UseMutationResult<
  BoolField,
  AppError,
  { id: BoolFieldId; input: UpdateBoolFieldInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<BoolFieldDto>(
        `/api/v1/bool-fields/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapBoolFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: boolFieldKeys.lists() })
    },
  })
}
