import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DateTimeFieldDto } from './api-types'
import type { DateTimeFieldId } from './ids'
import { mapCreateInputToDto, mapDateTimeFieldDtoToModel, mapUpdateInputToDto } from './mapper'
import type { CreateDateTimeFieldInput, DateTimeField, UpdateDateTimeFieldInput } from './model'
import { dateTimeFieldKeys } from './query-keys'

export function useCreateDateTimeField(): UseMutationResult<
  DateTimeField,
  AppError,
  CreateDateTimeFieldInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<DateTimeFieldDto>(
        '/api/v1/datetime-fields',
        mapCreateInputToDto(input),
      )
      return mapDateTimeFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: dateTimeFieldKeys.lists() })
    },
  })
}

export function useUpdateDateTimeField(): UseMutationResult<
  DateTimeField,
  AppError,
  { id: DateTimeFieldId; input: UpdateDateTimeFieldInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<DateTimeFieldDto>(
        `/api/v1/datetime-fields/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapDateTimeFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: dateTimeFieldKeys.lists() })
    },
  })
}
