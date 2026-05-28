import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { AdminCommentDto, CommentDto } from './api-types'
import { mapAdminCommentDtoToModel, mapCommentDtoToModel } from './mapper'
import type { AdminComment, Comment, PostCommentInput } from './model'
import { commentKeys } from './query-keys'

export function usePostComment(): UseMutationResult<Comment, AppError, PostCommentInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<CommentDto>(
        `/api/v1/entities/${String(input.entityId)}/comments`,
        {
          author_name: input.authorName,
          author_email: input.authorEmail,
          body: input.body,
          website: input.honeypot ?? '',
        },
      )
      return mapCommentDtoToModel(dto)
    },
    onSuccess: async (_data, input) => {
      await queryClient.invalidateQueries({ queryKey: commentKeys.byEntity(input.entityId) })
    },
  })
}

export function useApproveComment(): UseMutationResult<AdminComment, AppError, number> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      const dto = await apiClient.patch<AdminCommentDto>(
        `/api/v1/admin/comments/${String(id)}/approve`,
        {},
      )
      return mapAdminCommentDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: commentKeys.adminList() })
    },
  })
}

export function useDeleteComment(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/admin/comments/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: commentKeys.adminList() })
    },
  })
}
