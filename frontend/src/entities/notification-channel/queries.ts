import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { NotificationChannelListDto } from './api-types'
import { mapNotificationChannelListDtoToModel } from './mapper'
import type { NotificationChannelList } from './model'
import { notificationChannelKeys } from './query-keys'

export function useNotificationChannelList(): UseQueryResult<NotificationChannelList, AppError> {
  return useQuery({
    queryKey: notificationChannelKeys.list(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<NotificationChannelListDto>(
        '/api/v1/notification-channels',
        signal,
      )
      return mapNotificationChannelListDtoToModel(dto)
    },
  })
}
