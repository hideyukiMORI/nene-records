import type { TagId } from './ids'

export const tagKeys = {
  all: ['tags'] as const,
  lists: () => [...tagKeys.all, 'list'] as const,
  list: (params: { limit: number; offset: number }) => [...tagKeys.lists(), params] as const,
  details: () => [...tagKeys.all, 'detail'] as const,
  detail: (id: TagId) => [...tagKeys.details(), id] as const,
}
