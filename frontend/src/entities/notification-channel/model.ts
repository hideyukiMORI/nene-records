export type NotificationChannelType = 'email' | 'slack' | 'discord' | 'chatwork' | 'webhook'

export const NOTIFICATION_CHANNEL_TYPES: NotificationChannelType[] = [
  'email',
  'slack',
  'discord',
  'chatwork',
  'webhook',
]

export interface NotificationChannel {
  id: number
  channelType: NotificationChannelType
  label: string
  isEnabled: boolean
  config: Record<string, unknown>
  createdAt: string
  updatedAt: string
}

export interface NotificationChannelList {
  items: NotificationChannel[]
}

export interface CreateNotificationChannelInput {
  channelType: NotificationChannelType
  label: string
  isEnabled: boolean
  config: Record<string, unknown>
}

export interface UpdateNotificationChannelInput {
  label: string
  isEnabled: boolean
  config: Record<string, unknown>
}
