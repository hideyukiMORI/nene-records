import { Fragment, useMemo, useRef, useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, Stack, Text } from '@/shared/ui'
import {
  IconChevronDown,
  IconChevronUp,
  IconFileText,
  IconImage,
  IconLayers,
  IconLayout,
  IconMenu,
  IconMessageCircle,
  IconX,
} from '@/shared/ui/icons/Icons'
import {
  createBlock,
  parseBlocksDocument,
  serializeBlocksDocument,
  validateBlock,
  type Block,
  type BlockType,
  type CalloutBlockData,
  type ChartBlockData,
  type GalleryBlockData,
  type HeroBlockData,
  type TextBlockData,
} from '@/shared/lib/blocks-document'
import { BLOCK_CATALOG, blockCatalogEntry } from './block-catalog'
import {
  clearBlockDragPayload,
  computeBlockDropIndex,
  getBlockDragPayload,
  setBlockDragPayload,
} from './block-dnd'
import { BlockInspector } from './BlockInspector'

export interface BlocksFieldEditorProps {
  id: string
  label: string
  /** The stored field value: a JSON-string blocks document. */
  value: string
  disabled: boolean
  /** Restrict the palette to these block types (default: all). */
  allowedTypes?: readonly BlockType[]
  onChange: (value: string) => void
}

function blockTypeIcon(type: BlockType) {
  switch (type) {
    case 'text':
      return IconFileText
    case 'callout':
      return IconMessageCircle
    case 'hero':
      return IconLayout
    case 'gallery':
      return IconImage
    case 'chart':
      return IconLayers
  }
}

function rawSummary(block: Block): string {
  switch (block.type) {
    case 'text':
      return block.data.markdown
    case 'callout': {
      const title = block.data.title
      return title !== undefined && title.trim() !== '' ? title : block.data.body
    }
    case 'hero':
      return block.data.heading.replace(/\*/g, '')
    case 'gallery':
      return block.data.items.map((item) => item.caption ?? item.alt).join(', ')
    case 'chart':
      return block.data.title !== undefined && block.data.title.trim() !== ''
        ? block.data.title
        : block.data.summary
  }
}

function blockSummary(block: Block): string {
  const text = rawSummary(block).replace(/\s+/g, ' ').trim()
  return text.length > 80 ? `${text.slice(0, 80)}…` : text
}

/**
 * Inline editor for a `blocks` field (#486 S1b): palette + reorderable board +
 * inspector. Fully controlled — emits the serialized JSON document up through
 * `onChange`; the parent record form owns save / dirty / unsaved-guard.
 */
export function BlocksFieldEditor({
  id,
  label,
  value,
  disabled,
  allowedTypes,
  onChange,
}: BlocksFieldEditorProps) {
  const { t } = useTranslation()
  const palette = useMemo(
    () =>
      allowedTypes === undefined
        ? BLOCK_CATALOG
        : BLOCK_CATALOG.filter((entry) => allowedTypes.includes(entry.type)),
    [allowedTypes],
  )
  const blocks = useMemo(() => parseBlocksDocument(value), [value])
  const [selectedId, setSelectedId] = useState<string | null>(null)
  const [dropIndex, setDropIndex] = useState<number | null>(null)
  const boardRef = useRef<HTMLDivElement>(null)

  const selected = blocks.find((block) => block.id === selectedId) ?? null

  const emit = (next: Block[]) => {
    onChange(serializeBlocksDocument(next))
  }

  const addBlock = (type: BlockType) => {
    const block = createBlock(type)
    emit([...blocks, block])
    setSelectedId(block.id)
  }

  const deleteBlock = (blockId: string) => {
    emit(blocks.filter((block) => block.id !== blockId))
    if (selectedId === blockId) {
      setSelectedId(null)
    }
  }

  const moveBlock = (index: number, direction: -1 | 1) => {
    const target = index + direction
    if (target < 0 || target >= blocks.length) {
      return
    }
    const next = blocks.slice()
    const [moved] = next.splice(index, 1)
    next.splice(target, 0, moved)
    emit(next)
  }

  const reorder = (fromId: string, toIndex: number) => {
    const fromIndex = blocks.findIndex((block) => block.id === fromId)
    if (fromIndex === -1) {
      return
    }
    const next = blocks.slice()
    const [moved] = next.splice(fromIndex, 1)
    const adjusted = toIndex > fromIndex ? toIndex - 1 : toIndex
    next.splice(adjusted, 0, moved)
    emit(next)
  }

  const updateData = (
    blockId: string,
    data: TextBlockData | CalloutBlockData | HeroBlockData | GalleryBlockData | ChartBlockData,
  ) => {
    emit(
      blocks.map((block): Block => {
        if (block.id !== blockId) {
          return block
        }
        switch (block.type) {
          case 'text':
            return { ...block, data: data as TextBlockData }
          case 'callout':
            return { ...block, data: data as CalloutBlockData }
          case 'hero':
            return { ...block, data: data as HeroBlockData }
          case 'gallery':
            return { ...block, data: data as GalleryBlockData }
          case 'chart':
            return { ...block, data: data as ChartBlockData }
        }
      }),
    )
  }

  const handleDragOver = (event: React.DragEvent<HTMLDivElement>) => {
    if (disabled || getBlockDragPayload() === null || boardRef.current === null) {
      return
    }
    event.preventDefault()
    setDropIndex(computeBlockDropIndex(boardRef.current, event.clientY))
  }

  const handleDrop = (event: React.DragEvent<HTMLDivElement>) => {
    const payload = getBlockDragPayload()
    if (disabled || payload === null || boardRef.current === null) {
      return
    }
    event.preventDefault()
    const index = computeBlockDropIndex(boardRef.current, event.clientY)
    if (payload.kind === 'move') {
      reorder(payload.id, index)
    }
    clearBlockDragPayload()
    setDropIndex(null)
  }

  const dropLine = (index: number) => (
    <div
      aria-hidden="true"
      className={dropIndex === index ? 'border-t-2 border-accent' : 'border-t-2 border-transparent'}
    />
  )

  return (
    <div className="flex flex-col gap-stack-sm">
      <Text as="span" variant="caption" className="font-medium text-text-primary">
        {label}
      </Text>

      <div className="flex flex-wrap gap-inline-sm">
        {palette.map((entry) => (
          <Button
            key={entry.type}
            variant="secondary"
            size="sm"
            disabled={disabled}
            onClick={() => {
              addBlock(entry.type)
            }}
          >
            {t('admin.blocks.add', { type: t(entry.labelKey) })}
          </Button>
        ))}
      </div>

      {blocks.length === 0 ? (
        <EmptyState
          title={t('admin.blocks.empty.title')}
          description={t('admin.blocks.empty.description')}
        />
      ) : (
        <div
          ref={boardRef}
          className="flex flex-col gap-stack-xs"
          onDragOver={handleDragOver}
          onDrop={handleDrop}
          onDragLeave={() => {
            setDropIndex(null)
          }}
        >
          {blocks.map((block, index) => {
            const Icon = blockTypeIcon(block.type)
            const invalid = validateBlock(block) !== null
            const isSelected = block.id === selectedId
            return (
              <Fragment key={block.id}>
                {dropLine(index)}
                <Card
                  padding="row"
                  data-bcard=""
                  draggable={!disabled}
                  className={isSelected ? 'ring-2 ring-accent' : undefined}
                  onDragStart={(event) => {
                    setBlockDragPayload({ kind: 'move', id: block.id })
                    event.dataTransfer.effectAllowed = 'move'
                  }}
                  onDragEnd={() => {
                    clearBlockDragPayload()
                    setDropIndex(null)
                  }}
                >
                  <div className="flex items-center gap-inline-sm">
                    <span className="text-text-muted" aria-hidden="true">
                      <IconMenu size={15} />
                    </span>
                    <button
                      type="button"
                      className="flex min-w-0 flex-1 items-center gap-inline-sm text-left"
                      onClick={() => {
                        setSelectedId(block.id)
                      }}
                    >
                      <span className="text-text-muted" aria-hidden="true">
                        <Icon size={15} />
                      </span>
                      <span className="font-sans text-caption font-medium text-text-primary">
                        {t(blockCatalogEntry(block.type).labelKey)}
                      </span>
                      <span className="min-w-0 flex-1 truncate font-sans text-caption text-text-muted">
                        {blockSummary(block) === ''
                          ? t('admin.blocks.card.empty')
                          : blockSummary(block)}
                      </span>
                    </button>
                    {invalid ? (
                      <span className="shrink-0 font-sans text-caption text-danger">
                        {t('admin.blocks.card.invalid')}
                      </span>
                    ) : null}
                    <span className="flex items-center gap-inline-xs">
                      <button
                        type="button"
                        className="rounded p-1 text-text-muted hover:text-text-primary disabled:opacity-40"
                        title={t('admin.blocks.moveUp')}
                        disabled={disabled || index === 0}
                        onClick={() => {
                          moveBlock(index, -1)
                        }}
                      >
                        <IconChevronUp size={15} />
                      </button>
                      <button
                        type="button"
                        className="rounded p-1 text-text-muted hover:text-text-primary disabled:opacity-40"
                        title={t('admin.blocks.moveDown')}
                        disabled={disabled || index === blocks.length - 1}
                        onClick={() => {
                          moveBlock(index, 1)
                        }}
                      >
                        <IconChevronDown size={15} />
                      </button>
                      <button
                        type="button"
                        className="rounded p-1 text-text-muted hover:text-danger disabled:opacity-40"
                        title={t('common.actions.delete')}
                        disabled={disabled}
                        onClick={() => {
                          deleteBlock(block.id)
                        }}
                      >
                        <IconX size={15} />
                      </button>
                    </span>
                  </div>
                </Card>
              </Fragment>
            )
          })}
          {dropLine(blocks.length)}
        </div>
      )}

      {selected !== null ? (
        <Card padding="md">
          <Stack gap="sm">
            <div className="flex items-center justify-between">
              <Text as="span" variant="caption" muted>
                {t(blockCatalogEntry(selected.type).labelKey)}
              </Text>
              <button
                type="button"
                className="rounded p-1 text-text-muted hover:text-text-primary"
                title={t('admin.blocks.deselect')}
                onClick={() => {
                  setSelectedId(null)
                }}
              >
                <IconX size={15} />
              </button>
            </div>
            <BlockInspector
              block={selected}
              errorCode={validateBlock(selected)}
              disabled={disabled}
              idPrefix={`${id}-${selected.id}`}
              onChange={(data) => {
                updateData(selected.id, data)
              }}
            />
          </Stack>
        </Card>
      ) : null}
    </div>
  )
}
