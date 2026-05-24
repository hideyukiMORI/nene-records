import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { IntFieldDto } from './api-types'
import type { IntFieldId } from './ids'
import { mapCreateInputToDto, mapIntFieldDtoToModel, mapUpdateInputToDto } from './mapper'
import type { CreateIntFieldInput, IntField, UpdateIntFieldInput } from './model'
import { intFieldKeys } from './query-keys'

export function useCreateIntField(): UseMutationResult<IntField, AppError, CreateIntFieldInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<IntFieldDto>(
        '/api/v1/int-fields',
        mapCreateInputToDto(input),
      )
      return mapIntFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: intFieldKeys.lists() })
    },
  })
}

export function useUpdateIntField(): UseMutationResult<
  IntField,
  AppError,
  { id: IntFieldId; input: UpdateIntFieldInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<IntFieldDto>(
        `/api/v1/int-fields/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapIntFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: intFieldKeys.lists() })
    },
  })
}
