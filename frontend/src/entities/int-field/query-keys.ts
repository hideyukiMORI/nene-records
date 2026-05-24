import type { IntFieldId } from './ids'

export interface IntFieldListParams {
  limit: number
  offset: number
  entityId?: number
}

export const intFieldKeys = {
  all: ['int-fields'] as const,
  lists: () => [...intFieldKeys.all, 'list'] as const,
  list: (params: IntFieldListParams) => [...intFieldKeys.lists(), params] as const,
  details: () => [...intFieldKeys.all, 'detail'] as const,
  detail: (id: IntFieldId) => [...intFieldKeys.details(), id] as const,
}
