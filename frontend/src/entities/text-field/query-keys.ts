import type { TextFieldId } from './ids'

export interface TextFieldListParams {
  limit: number
  offset: number
  entityId?: number
  entityTypeId?: number
  locale?: string | null
}

export const textFieldKeys = {
  all: ['text-fields'] as const,
  lists: () => [...textFieldKeys.all, 'list'] as const,
  list: (params: TextFieldListParams) => [...textFieldKeys.lists(), params] as const,
  details: () => [...textFieldKeys.all, 'detail'] as const,
  detail: (id: TextFieldId) => [...textFieldKeys.details(), id] as const,
}
