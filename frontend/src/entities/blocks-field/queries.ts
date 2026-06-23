import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { BlocksFieldListDto } from './api-types'
import { mapBlocksFieldListDtoToModel } from './mapper'
import type { BlocksFieldList } from './model'
import { blocksFieldKeys, type BlocksFieldListParams } from './query-keys'

const DEFAULT_LIST_PARAMS = { limit: 100, offset: 0 } as const

export function useBlocksFieldList(
  params: BlocksFieldListParams = DEFAULT_LIST_PARAMS,
  options?: { enabled?: boolean },
): UseQueryResult<BlocksFieldList, AppError> {
  return useQuery({
    queryKey: blocksFieldKeys.list(params),
    enabled: options?.enabled ?? true,
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams({
        limit: String(params.limit),
        offset: String(params.offset),
      })
      if (params.entityId !== undefined) {
        search.set('entity_id', String(params.entityId))
      } else if (params.entityTypeId !== undefined) {
        search.set('entity_type_id', String(params.entityTypeId))
      }
      if (params.locale != null) {
        search.set('locale', params.locale)
      }
      const dto = await apiClient.get<BlocksFieldListDto>(
        `/api/v1/blocks-fields?${search.toString()}`,
        signal,
      )
      return mapBlocksFieldListDtoToModel(dto)
    },
  })
}
