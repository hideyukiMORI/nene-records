export const entityRelationKeys = {
  all: ['entity-relations'] as const,
  lists: () => [...entityRelationKeys.all, 'list'] as const,
  list: (entityId: number, fieldKey: string) =>
    [...entityRelationKeys.lists(), entityId, fieldKey] as const,
}
