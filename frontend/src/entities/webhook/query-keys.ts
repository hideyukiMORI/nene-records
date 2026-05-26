export const webhookKeys = {
  all: ['webhooks'] as const,
  list: () => [...webhookKeys.all, 'list'] as const,
  detail: (id: number) => [...webhookKeys.all, 'detail', id] as const,
}
