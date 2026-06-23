import type { BlocksFieldId } from './ids'

/**
 * A `blocks` field row. `value` is the JSON-string blocks document
 * (see `@/shared/lib/blocks-document`); this slice treats it as opaque.
 */
export interface BlocksField {
  id: BlocksFieldId
  entityId: number
  fieldKey: string
  value: string
  locale: string | null
}

export interface BlocksFieldList {
  items: BlocksField[]
  limit: number
  offset: number
}

export interface CreateBlocksFieldInput {
  entityId: number
  fieldKey: string
  value: string
  locale?: string | null
}

export interface UpdateBlocksFieldInput {
  fieldKey: string
  value: string
  locale?: string | null
}
