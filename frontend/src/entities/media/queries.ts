import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { MediaListDto } from './api-types'
import { mapMediaListDtoToModel } from './mapper'
import type { MediaList } from './model'
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
