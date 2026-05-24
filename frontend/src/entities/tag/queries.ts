import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { TagDto, TagListDto } from './api-types'
import type { TagId } from './ids'
import { mapTagDtoToModel, mapTagListDtoToModel } from './mapper'
import type { Tag, TagList } from './model'
import { tagKeys } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 20, offset: 0 } as const

export function useTagList(
  params: { limit: number; offset: number } = DEFAULT_LIST_PARAMS,
): UseQueryResult<TagList, AppError> {
  return useQuery({
    queryKey: tagKeys.list(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      const dto = await apiClient.get<TagListDto>(`/api/v1/tags?${search.toString()}`, signal)
      return mapTagListDtoToModel(dto)
    },
  })
}

export function useTag(id: TagId): UseQueryResult<Tag, AppError> {
  return useQuery({
    queryKey: tagKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<TagDto>(`/api/v1/tags/${String(id)}`, signal)
      return mapTagDtoToModel(dto)
    },
  })
}
