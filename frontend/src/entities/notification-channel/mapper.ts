import type { NotificationChannelDto, NotificationChannelListDto } from './api-types'
import type { NotificationChannel, NotificationChannelList } from './model'

export function mapNotificationChannelDtoToModel(dto: NotificationChannelDto): NotificationChannel {
  return {
    id: dto.id,
    channelType: dto.channel_type,
    label: dto.label,
    isEnabled: dto.is_enabled,
    config: dto.config,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapNotificationChannelListDtoToModel(
  dto: NotificationChannelListDto,
): NotificationChannelList {
  return {
    items: dto.items.map(mapNotificationChannelDtoToModel),
  }
}
