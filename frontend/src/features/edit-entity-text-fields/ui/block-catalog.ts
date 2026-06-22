import type { MessageKey } from '@/shared/i18n'
import type { BlockType } from '@/shared/lib/blocks-document'

export interface BlockCatalogEntry {
  type: BlockType
  labelKey: MessageKey
  descKey: MessageKey
}

/** Admin presentation for each block type. Mirrors the server BlockTypes whitelist. */
export const BLOCK_CATALOG: readonly BlockCatalogEntry[] = [
  { type: 'text', labelKey: 'admin.blocks.type.text', descKey: 'admin.blocks.type.text.desc' },
  {
    type: 'callout',
    labelKey: 'admin.blocks.type.callout',
    descKey: 'admin.blocks.type.callout.desc',
  },
]

const CATALOG_BY_TYPE = Object.fromEntries(
  BLOCK_CATALOG.map((entry) => [entry.type, entry]),
) as Record<BlockType, BlockCatalogEntry>

export function blockCatalogEntry(type: BlockType): BlockCatalogEntry {
  return CATALOG_BY_TYPE[type]
}
