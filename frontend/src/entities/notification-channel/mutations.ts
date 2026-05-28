import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { NotificationChannelDto } from './api-types'
import { mapNotificationChannelDtoToModel } from './mapper'
import type {
  CreateNotificationChannelInput,
  NotificationChannel,
  UpdateNotificationChannelInput,
} from './model'
import { notificationChannelKeys } from './query-keys'

export function useCreateNotificationChannel(): UseMutationResult<
  NotificationChannel,
  AppError,
  CreateNotificationChannelInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<NotificationChannelDto>('/api/v1/notification-channels', {
        channel_type: input.channelType,
        label: input.label,
        is_enabled: input.isEnabled,
        config: input.config,
      })
      return mapNotificationChannelDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: notificationChannelKeys.list() })
    },
  })
}

export function useUpdateNotificationChannel(): UseMutationResult<
  void,
  AppError,
  { id: number; input: UpdateNotificationChannelInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      await apiClient.patch(`/api/v1/notification-channels/${String(id)}`, {
        label: input.label,
        is_enabled: input.isEnabled,
        config: input.config,
      })
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: notificationChannelKeys.list() })
    },
  })
}

export function useDeleteNotificationChannel(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/notification-channels/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: notificationChannelKeys.list() })
    },
  })
}

export function useTestNotificationChannel(): UseMutationResult<void, AppError, number> {
  return useMutation({
    mutationFn: async (id) => {
      await apiClient.post(`/api/v1/notification-channels/${String(id)}/test`, {})
    },
  })
}
