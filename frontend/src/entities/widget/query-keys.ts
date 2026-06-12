export const widgetKeys = {
  all: ['widgets'] as const,
  adminList: () => [...widgetKeys.all, 'admin-list'] as const,
  publicList: () => [...widgetKeys.all, 'public-list'] as const,
}
