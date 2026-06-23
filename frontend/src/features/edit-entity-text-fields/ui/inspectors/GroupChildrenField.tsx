import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Button } from '@/shared/ui'
import { IconChevronDown, IconChevronUp, IconX } from '@/shared/ui/icons/Icons'
import {
  createBlock,
  validateBlock,
  type BlockData,
  type BlockType,
  type LeafBlock,
} from '@/shared/lib/blocks-document'
import { moveItem } from '@/shared/lib/move-item'
import { BLOCK_CATALOG, blockCatalogEntry } from '../block-catalog'
import { BlockInspector } from '../BlockInspector'

/** Leaf block types a container (group / columns) may hold (no nesting; depth 2). */
const GROUP_CHILD_CATALOG = BLOCK_CATALOG.filter(
  (entry) => entry.type !== 'group' && entry.type !== 'columns',
)

interface GroupChildrenFieldProps {
  idPrefix: string
  items: LeafBlock[]
  disabled: boolean
  error?: string | undefined
  /** Max children (mirrors the server cap); the add palette disables at the limit. */
  maxItems: number
  onChange: (items: LeafBlock[]) => void
}

/**
 * Sub-editor for a group's child blocks (#491 WS2): a leaf-only palette + a
 * reorderable list where each child expands to its own BlockInspector. Children
 * are leaf blocks, so the recursion terminates at one level (depth 2).
 */
export function GroupChildrenField({
  idPrefix,
  items,
  disabled,
  error,
  maxItems,
  onChange,
}: GroupChildrenFieldProps) {
  const { t } = useTranslation()
  const [openIndex, setOpenIndex] = useState<number | null>(null)
  const atCapacity = items.length >= maxItems

  const add = (type: BlockType) => {
    onChange([...items, createBlock(type) as LeafBlock])
    setOpenIndex(items.length)
  }
  const update = (index: number, data: BlockData) => {
    onChange(items.map((item, i) => (i === index ? ({ ...item, data } as LeafBlock) : item)))
  }
  const move = (index: number, direction: -1 | 1) => {
    const next = moveItem(items, index, direction)
    if (next !== null) {
      onChange(next)
      setOpenIndex(null)
    }
  }
  const remove = (index: number) => {
    onChange(items.filter((_, i) => i !== index))
    setOpenIndex(null)
  }

  return (
    <div className="flex flex-col gap-stack-sm">
      <span className="font-sans text-caption font-medium text-text-primary">
        {t('admin.blocks.field.children')}
      </span>
      {error !== undefined ? (
        <span role="alert" className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
      <div className="flex flex-wrap gap-inline-sm">
        {GROUP_CHILD_CATALOG.map((entry) => (
          <Button
            key={entry.type}
            type="button"
            variant="ghost"
            size="sm"
            disabled={disabled || atCapacity}
            onClick={() => {
              add(entry.type)
            }}
          >
            {t('admin.blocks.add', { type: t(entry.labelKey) })}
          </Button>
        ))}
      </div>
      {items.map((item, index) => (
        <div
          key={item.id}
          className="flex flex-col gap-stack-xs rounded-md border border-border p-inline-sm"
        >
          <div className="flex items-center gap-inline-sm">
            <span className="flex-1 font-sans text-caption font-medium text-text-primary">
              {t(blockCatalogEntry(item.type).labelKey)}
            </span>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={disabled || index === 0}
              title={t('admin.blocks.moveUp')}
              onClick={() => {
                move(index, -1)
              }}
            >
              <IconChevronUp size={15} />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={disabled || index === items.length - 1}
              title={t('admin.blocks.moveDown')}
              onClick={() => {
                move(index, 1)
              }}
            >
              <IconChevronDown size={15} />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={disabled}
              onClick={() => {
                setOpenIndex(openIndex === index ? null : index)
              }}
            >
              {openIndex === index ? t('admin.blocks.childCollapse') : t('admin.blocks.childEdit')}
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={disabled}
              title={t('common.actions.remove')}
              onClick={() => {
                remove(index)
              }}
            >
              <IconX size={15} />
            </Button>
          </div>
          {openIndex === index ? (
            <BlockInspector
              block={item}
              errorCode={validateBlock(item)}
              disabled={disabled}
              idPrefix={`${idPrefix}-c${String(index)}`}
              onChange={(data) => {
                update(index, data)
              }}
            />
          ) : null}
        </div>
      ))}
    </div>
  )
}
