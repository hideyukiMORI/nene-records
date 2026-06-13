import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { PopularEntitiesResponseDto } from './api-types'
import { mapPopularEntityListDtoToModel } from './mapper'
import type { PopularEntityList } from './model'
import { popularEntityKeys } from './query-keys'

/** Most-viewed published entities over the last `days` days. */
export function usePopularEntities(options?: {
  days?: number
  limit?: number
}): UseQueryResult<PopularEntityList, AppError> {
  const days = options?.days ?? 30
  const limit = options?.limit ?? 5
  return useQuery({
    queryKey: popularEntityKeys.list(days, limit),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({ days: String(days), limit: String(limit) })
      const dto = await apiClient.get<PopularEntitiesResponseDto>(
        `/api/v1/analytics/popular-entities?${search.toString()}`,
        signal,
      )
      return mapPopularEntityListDtoToModel(dto)
    },
  })
}
