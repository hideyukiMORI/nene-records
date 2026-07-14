export interface WebhookDto {
  id: number
  url: string
  events: string[]
  entity_type_id: number | null
  /** Write-only secret: never returned on read; only whether one is configured. */
  has_secret: boolean
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface WebhookListDto {
  items: WebhookDto[]
}

export interface CreateWebhookRequestDto {
  url: string
  events: string[]
  entity_type_id: number | null
  secret: string | null
  is_active: boolean
}

export interface UpdateWebhookRequestDto {
  url: string
  events: string[]
  entity_type_id: number | null
  secret: string | null
  is_active: boolean
}
