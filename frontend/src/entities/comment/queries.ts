import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { AdminCommentListDto, CommentListDto } from './api-types'
import { mapAdminCommentListDtoToModel, mapCommentListDtoToModel } from './mapper'
import type { AdminCommentList, CommentList } from './model'
import { commentKeys } from './query-keys'

export function useCommentList(entityId: number): UseQueryResult<CommentList, AppError> {
  return useQuery({
    queryKey: commentKeys.byEntity(entityId),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<CommentListDto>(
        `/api/v1/entities/${String(entityId)}/comments`,
        signal,
      )
      return mapCommentListDtoToModel(dto)
    },
  })
}

export function useAdminCommentList(): UseQueryResult<AdminCommentList, AppError> {
  return useQuery({
    queryKey: commentKeys.adminList(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<AdminCommentListDto>('/api/v1/admin/comments', signal)
      return mapAdminCommentListDtoToModel(dto)
    },
  })
}
