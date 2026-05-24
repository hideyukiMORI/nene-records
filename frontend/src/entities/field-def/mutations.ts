import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { FieldDefDto } from './api-types'
import type { FieldDefId } from './ids'
import { mapCreateInputToDto, mapFieldDefDtoToModel } from './mapper'
import type { CreateFieldDefInput, FieldDef } from './model'
import { fieldDefKeys } from './query-keys'

export function useCreateFieldDef(): UseMutationResult<FieldDef, AppError, CreateFieldDefInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<FieldDefDto>(
        '/api/v1/field-defs',
        mapCreateInputToDto(input),
      )
      return mapFieldDefDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: fieldDefKeys.lists(),
        predicate: (query) => {
          const params = query.queryKey[2]
          return (
            typeof params === 'object' &&
            params !== null &&
            'entityTypeId' in params &&
            params.entityTypeId === variables.entityTypeId
          )
        },
      })
    },
  })
}

export function useDeleteFieldDef(): UseMutationResult<
  void,
  AppError,
  { id: FieldDefId; entityTypeId: number }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id }) => {
      await apiClient.delete(`/api/v1/field-defs/${String(id)}`)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: fieldDefKeys.lists(),
        predicate: (query) => {
          const params = query.queryKey[2]
          return (
            typeof params === 'object' &&
            params !== null &&
            'entityTypeId' in params &&
            params.entityTypeId === variables.entityTypeId
          )
        },
      })
    },
  })
}
