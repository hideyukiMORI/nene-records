import type { EntityTypeId } from './ids'

export const entityTypeKeys = {
  all: ['entity-types'] as const,
  lists: () => [...entityTypeKeys.all, 'list'] as const,
  list: (params: { limit: number; offset: number }) => [...entityTypeKeys.lists(), params] as const,
  details: () => [...entityTypeKeys.all, 'detail'] as const,
  detail: (id: EntityTypeId) => [...entityTypeKeys.details(), id] as const,
}
