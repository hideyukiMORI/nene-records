import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { TagDto } from './api-types'
import type { TagId } from './ids'
import { mapCreateInputToDto, mapTagDtoToModel, mapUpdateInputToDto } from './mapper'
import type { CreateTagInput, Tag, UpdateTagInput } from './model'
import { tagKeys } from './query-keys'

export function useCreateTag(): UseMutationResult<Tag, AppError, CreateTagInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<TagDto>('/api/v1/tags', mapCreateInputToDto(input))
      return mapTagDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: tagKeys.lists() })
    },
  })
}

export function useUpdateTag(): UseMutationResult<
  Tag,
  AppError,
  { id: TagId; input: UpdateTagInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<TagDto>(
        `/api/v1/tags/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapTagDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({ queryKey: tagKeys.lists() })
      await queryClient.invalidateQueries({ queryKey: tagKeys.detail(variables.id) })
    },
  })
}

export function useDeleteTag(): UseMutationResult<void, AppError, TagId> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/tags/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: tagKeys.lists() })
    },
  })
}
