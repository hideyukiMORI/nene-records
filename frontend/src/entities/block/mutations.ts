import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { BlocksFieldDto } from './api-types'
import type { BlocksFieldId } from './ids'
import { mapBlocksFieldDtoToModel, mapCreateInputToDto, mapUpdateInputToDto } from './mapper'
import type { BlocksField, CreateBlocksFieldInput, UpdateBlocksFieldInput } from './model'
import { blocksFieldKeys } from './query-keys'

export function useCreateBlocksField(): UseMutationResult<
  BlocksField,
  AppError,
  CreateBlocksFieldInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<BlocksFieldDto>(
        '/api/v1/blocks-fields',
        mapCreateInputToDto(input),
      )
      return mapBlocksFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: blocksFieldKeys.lists() })
    },
  })
}

export function useUpdateBlocksField(): UseMutationResult<
  BlocksField,
  AppError,
  { id: BlocksFieldId; input: UpdateBlocksFieldInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<BlocksFieldDto>(
        `/api/v1/blocks-fields/${String(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapBlocksFieldDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: blocksFieldKeys.lists() })
    },
  })
}
