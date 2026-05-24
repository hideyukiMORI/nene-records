import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { FieldDefDto } from './api-types'
import type { FieldDefId } from './ids'
import { mapCreateInputToDto, mapFieldDefDtoToModel, mapUpdateInputToDto } from './mapper'
import type { CreateFieldDefInput, FieldDef, UpdateFieldDefInput } from './model'
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

export function useUpdateFieldDef(): UseMutationResult<
  FieldDef,
  AppError,
  { id: FieldDefId; input: UpdateFieldDefInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<FieldDefDto>(
        `/api/v1/field-defs/${String(id)}`,
        mapUpdateInputToDto(input),
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
            params.entityTypeId === variables.input.entityTypeId
          )
        },
      })
      await queryClient.invalidateQueries({
        queryKey: fieldDefKeys.detail(variables.id),
      })
    },
  })
}
