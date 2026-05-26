import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { WebhookDto } from './api-types'
import { mapWebhookDtoToModel } from './mapper'
import type { CreateWebhookInput, UpdateWebhookInput, Webhook } from './model'
import { webhookKeys } from './query-keys'

export function useCreateWebhook(): UseMutationResult<Webhook, AppError, CreateWebhookInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<WebhookDto>('/api/v1/webhooks', {
        url: input.url,
        events: input.events,
        entity_type_id: input.entityTypeId,
        secret: input.secret !== '' ? input.secret : null,
        is_active: input.isActive,
      })
      return mapWebhookDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: webhookKeys.list() })
    },
  })
}

export function useUpdateWebhook(): UseMutationResult<
  Webhook,
  AppError,
  { id: number; input: UpdateWebhookInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.put<WebhookDto>(`/api/v1/webhooks/${String(id)}`, {
        url: input.url,
        events: input.events,
        entity_type_id: input.entityTypeId,
        secret: input.secret !== '' ? input.secret : null,
        is_active: input.isActive,
      })
      return mapWebhookDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: webhookKeys.list() })
    },
  })
}

export function useDeleteWebhook(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/webhooks/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: webhookKeys.list() })
    },
  })
}
