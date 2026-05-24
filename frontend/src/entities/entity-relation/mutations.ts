import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityRelationItemDto } from './api-types'
import { mapAttachInputToDto, mapEntityRelationItemDtoToModel } from './mapper'
import type { AttachEntityRelationInput, DetachEntityRelationInput, EntityRelation } from './model'
import { entityRelationKeys } from './query-keys'

export function useAttachEntityRelation(): UseMutationResult<
  EntityRelation,
  AppError,
  AttachEntityRelationInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<EntityRelationItemDto>(
        `/api/v1/entities/${String(input.entityId)}/relations`,
        mapAttachInputToDto(input),
      )
      return mapEntityRelationItemDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: entityRelationKeys.list(variables.entityId, variables.fieldKey),
      })
    },
  })
}

export function useDetachEntityRelation(): UseMutationResult<
  void,
  AppError,
  DetachEntityRelationInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const search = new URLSearchParams({ field_key: input.fieldKey })
      await apiClient.delete(
        `/api/v1/entities/${String(input.entityId)}/relations/${String(input.targetEntityId)}?${search.toString()}`,
      )
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: entityRelationKeys.list(variables.entityId, variables.fieldKey),
      })
    },
  })
}
