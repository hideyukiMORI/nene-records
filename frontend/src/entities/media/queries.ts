import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { MediaListDto, MediaUsageListDto } from './api-types'
import { mapMediaListDtoToModel, mapMediaUsageListDtoToModel } from './mapper'
import type { MediaList, MediaUsageList } from './model'
import { mediaKeys } from './query-keys'

export function useMediaList(): UseQueryResult<MediaList, AppError> {
  return useQuery({
    queryKey: mediaKeys.list(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<MediaListDto>('/api/v1/media', signal)
      return mapMediaListDtoToModel(dto)
    },
  })
}

/**
 * Reverse-lookup of a media item's usages. Pass `null` to keep the query idle
 * (e.g. until a delete dialog targets a specific item).
 */
export function useMediaUsages(id: number | null): UseQueryResult<MediaUsageList, AppError> {
  return useQuery({
    queryKey: mediaKeys.usages(id ?? 0),
    enabled: id !== null,
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<MediaUsageListDto>(
        `/api/v1/media/${String(id)}/usages`,
        signal,
      )
      return mapMediaUsageListDtoToModel(dto)
    },
  })
}
