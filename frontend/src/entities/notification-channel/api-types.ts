export type NotificationChannelType = 'email' | 'slack' | 'discord' | 'chatwork' | 'webhook'

export interface NotificationChannelDto {
  id: number
  channel_type: NotificationChannelType
  label: string
  is_enabled: boolean
  config: Record<string, unknown>
  created_at: string
  updated_at: string
}

export interface NotificationChannelListDto {
  items: NotificationChannelDto[]
}

export interface CreateNotificationChannelRequestDto {
  channel_type: NotificationChannelType
  label: string
  is_enabled: boolean
  config: Record<string, unknown>
}

export interface UpdateNotificationChannelRequestDto {
  label: string
  is_enabled: boolean
  config: Record<string, unknown>
}
