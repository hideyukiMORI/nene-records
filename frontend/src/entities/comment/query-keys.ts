export const commentKeys = {
  all: ['comments'] as const,
  byEntity: (entityId: number) => [...commentKeys.all, 'entity', entityId] as const,
  adminList: () => [...commentKeys.all, 'admin-list'] as const,
}
