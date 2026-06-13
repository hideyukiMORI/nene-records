export const menuKeys = {
  all: ['menus'] as const,
  adminList: () => [...menuKeys.all, 'admin-list'] as const,
  publicList: () => [...menuKeys.all, 'public-list'] as const,
}
