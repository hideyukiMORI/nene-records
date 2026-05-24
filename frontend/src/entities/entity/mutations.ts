import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityDto } from './api-types'
import type { EntityId } from './ids'
import { mapCreateInputToDto, mapEntityDtoToModel } from './mapper'
import type { CreateEntityInput, Entity } from './model'
import { entityKeys } from './query-keys'

export function useCreateEntity(): UseMutationResult<Entity, AppError, CreateEntityInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<EntityDto>('/api/v1/entities', mapCreateInputToDto(input))
      return mapEntityDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: entityKeys.lists(),
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

export function useDeleteEntity(): UseMutationResult<
  void,
  AppError,
  { id: EntityId; entityTypeId: number }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id }) => {
      await apiClient.delete(`/api/v1/entities/${String(id)}`)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: entityKeys.lists(),
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
