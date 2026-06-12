import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityTypeDto } from './api-types'
import type { EntityTypeId } from './ids'
import { mapCreateInputToDto, mapEntityTypeDtoToModel, mapUpdateInputToDto } from './mapper'
import type { CreateEntityTypeInput, EntityType, UpdateEntityTypeInput } from './model'
import { entityTypeKeys } from './query-keys'

export function useCreateEntityType(): UseMutationResult<
  EntityType,
  AppError,
  CreateEntityTypeInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<EntityTypeDto>(
        '/api/v1/entity-types',
        mapCreateInputToDto(input),
      )
      return mapEntityTypeDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: entityTypeKeys.lists() })
    },
  })
}

export function useUpdateEntityType(): UseMutationResult<
  EntityType,
  AppError,
  { id: EntityTypeId; input: UpdateEntityTypeInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<EntityTypeDto>(
        `/api/v1/entity-types/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapEntityTypeDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({ queryKey: entityTypeKeys.lists() })
      await queryClient.invalidateQueries({
        queryKey: entityTypeKeys.detail(variables.id),
      })
    },
  })
}

/**
 * Persists a new sidebar/pinned display order. Pass entity type ids in the
 * desired order; the server assigns display_order = index.
 */
export function useReorderEntityTypes(): UseMutationResult<void, AppError, EntityTypeId[]> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (ids) => {
      await apiClient.put<undefined>('/api/v1/entity-types/reorder', {
        ids: ids.map((id) => Number(id)),
      })
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: entityTypeKeys.lists() })
    },
  })
}

export function useDeleteEntityType(): UseMutationResult<void, AppError, EntityTypeId> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/entity-types/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: entityTypeKeys.lists() })
    },
  })
}
