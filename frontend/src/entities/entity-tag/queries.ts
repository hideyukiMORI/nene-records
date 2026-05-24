import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityTagListDto } from './api-types'
import { mapEntityTagListDtoToModel } from './mapper'
import type { EntityTagList } from './model'
import { entityTagKeys } from './query-keys'

export function useEntityTagList(
  entityId: number,
  options?: { enabled?: boolean },
): UseQueryResult<EntityTagList, AppError> {
  return useQuery({
    queryKey: entityTagKeys.list(entityId),
    enabled: options?.enabled ?? entityId > 0,
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<EntityTagListDto>(
        `/api/v1/entities/${String(entityId)}/tags`,
        signal,
      )
      return mapEntityTagListDtoToModel(dto)
    },
  })
}
