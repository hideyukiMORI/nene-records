import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { WebhookListDto } from './api-types'
import { mapWebhookListDtoToModel } from './mapper'
import type { WebhookList } from './model'
import { webhookKeys } from './query-keys'

export function useWebhookList(): UseQueryResult<WebhookList, AppError> {
  return useQuery({
    queryKey: webhookKeys.list(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<WebhookListDto>('/api/v1/webhooks', signal)
      return mapWebhookListDtoToModel(dto)
    },
  })
}
