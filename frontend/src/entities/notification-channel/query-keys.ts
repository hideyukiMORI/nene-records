export const notificationChannelKeys = {
  all: ['notification-channels'] as const,
  list: () => [...notificationChannelKeys.all, 'list'] as const,
}
