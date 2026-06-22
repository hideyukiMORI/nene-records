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
  { type: 'hero', labelKey: 'admin.blocks.type.hero', descKey: 'admin.blocks.type.hero.desc' },
  {
    type: 'gallery',
    labelKey: 'admin.blocks.type.gallery',
    descKey: 'admin.blocks.type.gallery.desc',
  },
  { type: 'chart', labelKey: 'admin.blocks.type.chart', descKey: 'admin.blocks.type.chart.desc' },
  { type: 'group', labelKey: 'admin.blocks.type.group', descKey: 'admin.blocks.type.group.desc' },
  {
    type: 'columns',
    labelKey: 'admin.blocks.type.columns',
    descKey: 'admin.blocks.type.columns.desc',
  },
]

const CATALOG_BY_TYPE = Object.fromEntries(
  BLOCK_CATALOG.map((entry) => [entry.type, entry]),
) as Record<BlockType, BlockCatalogEntry>

export function blockCatalogEntry(type: BlockType): BlockCatalogEntry {
  return CATALOG_BY_TYPE[type]
}
