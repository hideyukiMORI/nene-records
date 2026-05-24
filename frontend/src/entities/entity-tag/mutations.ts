import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityTagDto } from './api-types'
import { mapAttachInputToDto, mapEntityTagDtoToModel } from './mapper'
import type { AttachEntityTagInput, DetachEntityTagInput, EntityTag } from './model'
import { entityTagKeys } from './query-keys'

export function useAttachEntityTag(): UseMutationResult<EntityTag, AppError, AttachEntityTagInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<EntityTagDto>(
        `/api/v1/entities/${String(input.entityId)}/tags`,
        mapAttachInputToDto(input),
      )
      return mapEntityTagDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: entityTagKeys.list(variables.entityId),
      })
    },
  })
}

export function useDetachEntityTag(): UseMutationResult<void, AppError, DetachEntityTagInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      await apiClient.delete(
        `/api/v1/entities/${String(input.entityId)}/tags/${String(input.tagId)}`,
      )
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({
        queryKey: entityTagKeys.list(variables.entityId),
      })
    },
  })
}
