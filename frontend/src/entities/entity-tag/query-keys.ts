export const entityTagKeys = {
  all: ['entity-tags'] as const,
  lists: () => [...entityTagKeys.all, 'list'] as const,
  list: (entityId: number) => [...entityTagKeys.lists(), entityId] as const,
}
