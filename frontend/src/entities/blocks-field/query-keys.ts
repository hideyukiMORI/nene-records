import type { BlocksFieldId } from './ids'

export interface BlocksFieldListParams {
  limit: number
  offset: number
  entityId?: number
  entityTypeId?: number
  locale?: string | null
}

export const blocksFieldKeys = {
  all: ['blocks-fields'] as const,
  lists: () => [...blocksFieldKeys.all, 'list'] as const,
  list: (params: BlocksFieldListParams) => [...blocksFieldKeys.lists(), params] as const,
  details: () => [...blocksFieldKeys.all, 'detail'] as const,
  detail: (id: BlocksFieldId) => [...blocksFieldKeys.details(), id] as const,
}
