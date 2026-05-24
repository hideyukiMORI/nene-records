import type { EnumFieldId } from './ids'

export interface EnumFieldListParams {
  limit: number
  offset: number
  entityId?: number
}

export const enumFieldKeys = {
  all: ['enum-fields'] as const,
  lists: () => [...enumFieldKeys.all, 'list'] as const,
  list: (params: EnumFieldListParams) => [...enumFieldKeys.lists(), params] as const,
  details: () => [...enumFieldKeys.all, 'detail'] as const,
  detail: (id: EnumFieldId) => [...enumFieldKeys.details(), id] as const,
}
