import type { WebhookDto, WebhookListDto } from './api-types'
import type { Webhook, WebhookEvent, WebhookList } from './model'

export function mapWebhookDtoToModel(dto: WebhookDto): Webhook {
  return {
    id: dto.id,
    url: dto.url,
    events: dto.events as WebhookEvent[],
    entityTypeId: dto.entity_type_id,
    secret: dto.secret,
    isActive: dto.is_active,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapWebhookListDtoToModel(dto: WebhookListDto): WebhookList {
  return {
    items: dto.items.map(mapWebhookDtoToModel),
  }
}
