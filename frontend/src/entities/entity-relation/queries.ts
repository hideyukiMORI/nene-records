import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { EntityRelationListDto } from './api-types'
import { mapEntityRelationListDtoToModel } from './mapper'
import type { EntityRelationList } from './model'
import { entityRelationKeys } from './query-keys'

export function useEntityRelationList(
  entityId: number,
  fieldKey: string,
  options?: { enabled?: boolean },
): UseQueryResult<EntityRelationList, AppError> {
  return useQuery({
    queryKey: entityRelationKeys.list(entityId, fieldKey),
    enabled: options?.enabled ?? (entityId > 0 && fieldKey.length > 0),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({ field_key: fieldKey })
      const dto = await apiClient.get<EntityRelationListDto>(
        `/api/v1/entities/${String(entityId)}/relations?${search.toString()}`,
        signal,
      )
      return mapEntityRelationListDtoToModel(dto)
    },
  })
}
