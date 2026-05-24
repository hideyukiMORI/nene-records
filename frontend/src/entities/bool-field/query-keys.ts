import type { BoolFieldId } from './ids'

export interface BoolFieldListParams {
  limit: number
  offset: number
  entityId?: number
}

export const boolFieldKeys = {
  all: ['bool-fields'] as const,
  lists: () => [...boolFieldKeys.all, 'list'] as const,
  list: (params: BoolFieldListParams) => [...boolFieldKeys.lists(), params] as const,
  details: () => [...boolFieldKeys.all, 'detail'] as const,
  detail: (id: BoolFieldId) => [...boolFieldKeys.details(), id] as const,
}
