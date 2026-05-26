export type WebhookEvent = 'entity.created' | 'entity.updated' | 'entity.deleted'

export const WEBHOOK_EVENTS: WebhookEvent[] = ['entity.created', 'entity.updated', 'entity.deleted']

export interface Webhook {
  id: number
  url: string
  events: WebhookEvent[]
  entityTypeId: number | null
  secret: string | null
  isActive: boolean
  createdAt: string
  updatedAt: string
}

export interface WebhookList {
  items: Webhook[]
}

export interface CreateWebhookInput {
  url: string
  events: WebhookEvent[]
  entityTypeId: number | null
  secret: string | null
  isActive: boolean
}

export interface UpdateWebhookInput {
  url: string
  events: WebhookEvent[]
  entityTypeId: number | null
  secret: string | null
  isActive: boolean
}
