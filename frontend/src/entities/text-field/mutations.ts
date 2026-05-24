import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { TextFieldDto } from './api-types'
import type { TextFieldId } from './ids'
import { mapCreateInputToDto, mapTextFieldDtoToModel, mapUpdateInputToDto } from './mapper'
import type { CreateTextFieldInput, TextField, UpdateTextFieldInput } from './model'
import { textFieldKeys } from './query-keys'

export function useCreateTextField(): UseMutationResult<TextField, AppError, CreateTextFieldInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<TextFieldDto>(
        '/api/v1/text-fields',
        mapCreateInputToDto(input),
      )
      return mapTextFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: textFieldKeys.lists() })
    },
  })
}

export function useUpdateTextField(): UseMutationResult<
  TextField,
  AppError,
  { id: TextFieldId; input: UpdateTextFieldInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<TextFieldDto>(
        `/api/v1/text-fields/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapTextFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: textFieldKeys.lists() })
    },
  })
}
