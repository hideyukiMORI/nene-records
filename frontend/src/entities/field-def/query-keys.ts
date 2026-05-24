import type { FieldDefId } from './ids'

export interface FieldDefListParams {
  entityTypeId?: number
  limit: number
  offset: number
}

export const fieldDefKeys = {
  all: ['field-defs'] as const,
  lists: () => [...fieldDefKeys.all, 'list'] as const,
  list: (params: FieldDefListParams) => [...fieldDefKeys.lists(), params] as const,
  details: () => [...fieldDefKeys.all, 'detail'] as const,
  detail: (id: FieldDefId) => [...fieldDefKeys.details(), id] as const,
}
