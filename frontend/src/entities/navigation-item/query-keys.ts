export const navigationItemKeys = {
  all: ['navigation-items'] as const,
  adminList: () => [...navigationItemKeys.all, 'admin-list'] as const,
  publicList: () => [...navigationItemKeys.all, 'public-list'] as const,
}
