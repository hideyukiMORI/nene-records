import type { DateTimeFieldId } from './ids'

export interface DateTimeFieldListParams {
  limit: number
  offset: number
  entityId?: number
}

export const dateTimeFieldKeys = {
  all: ['datetime-fields'] as const,
  lists: () => [...dateTimeFieldKeys.all, 'list'] as const,
  list: (params: DateTimeFieldListParams) => [...dateTimeFieldKeys.lists(), params] as const,
  details: () => [...dateTimeFieldKeys.all, 'detail'] as const,
  detail: (id: DateTimeFieldId) => [...dateTimeFieldKeys.details(), id] as const,
}
