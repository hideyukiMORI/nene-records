import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EnumFieldDto } from './api-types'
import type { EnumFieldId } from './ids'
import { mapCreateInputToDto, mapEnumFieldDtoToModel, mapUpdateInputToDto } from './mapper'
import type { CreateEnumFieldInput, EnumField, UpdateEnumFieldInput } from './model'
import { enumFieldKeys } from './query-keys'

export function useCreateEnumField(): UseMutationResult<EnumField, AppError, CreateEnumFieldInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<EnumFieldDto>(
        '/api/v1/enum-fields',
        mapCreateInputToDto(input),
      )
      return mapEnumFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: enumFieldKeys.lists() })
    },
  })
}

export function useUpdateEnumField(): UseMutationResult<
  EnumField,
  AppError,
  { id: EnumFieldId; input: UpdateEnumFieldInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<EnumFieldDto>(
        `/api/v1/enum-fields/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapEnumFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: enumFieldKeys.lists() })
    },
  })
}
