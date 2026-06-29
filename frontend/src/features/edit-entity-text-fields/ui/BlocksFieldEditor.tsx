import { Fragment, type ReactNode, useMemo, useRef, useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { useMediaQuery } from '@/shared/lib/use-media-query'
import { Button, Card, EmptyState, Modal, Stack, Text } from '@/shared/ui'
import {
  IconChevronDown,
  IconChevronUp,
  IconCopy,
  IconFileText,
  IconImage,
  IconLayers,
  IconLayout,
  IconMenu,
  IconMessageCircle,
  IconX,
} from '@/shared/ui/icons/Icons'
import {
  MAX_BLOCKS_PER_DOCUMENT,
  createBlock,
  parseBlocksDocument,
  serializeBlocksDocument,
  validateBlock,
  type Block,
  type BlockData,
  type BlockType,
} from '@/shared/lib/blocks-document'
import { moveItem, reorderItem } from '@/shared/lib/move-item'
import { BLOCK_CATALOG, blockCatalogEntry } from './block-catalog'
import {
  clearBlockDragPayload,
  computeBlockDropIndex,
  getBlockDragPayload,
  setBlockDragPayload,
} from './block-dnd'
import { BlockInspector } from './BlockInspector'
import { BlocksPreview } from './BlocksPreview'
import { RepeaterIconButton } from './inspectors/RepeaterIconButton'

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
    case 'group':
      return IconCopy
    case 'columns':
      return IconLayout
    case 'spacer':
      return IconChevronDown
    case 'divider':
      return IconMenu
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
    case 'group':
      return block.data.children.map((child) => rawSummary(child)).join(' · ')
    case 'columns':
      return block.data.columns
        .flatMap((column) => column.children.map((child) => rawSummary(child)))
        .join(' · ')
    case 'spacer':
    case 'divider':
      return ''
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
  // Match the handoff's ≤820px breakpoint. Below it, touch DnD is unavailable
  // (grip hidden, cards not draggable), the card row stacks, and the inspector
  // opens as a modal instead of an inline panel.
  const isMobile = useMediaQuery('(max-width: 820px)')
  const inspectorTitleId = `${id}-inspector-title`
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
  const [showPreview, setShowPreview] = useState(false)
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
    const next = moveItem(blocks, index, direction)
    if (next !== null) {
      emit(next)
    }
  }

  const reorder = (fromId: string, toIndex: number) => {
    const next = reorderItem(
      blocks,
      blocks.findIndex((block) => block.id === fromId),
      toIndex,
    )
    if (next !== null) {
      emit(next)
    }
  }

  const updateData = (blockId: string, data: BlockData) => {
    // `data` is the inspector's matching variant for this block; narrowing the
    // pair per type would just re-cast, so apply it directly (the inspector only
    // emits the data shape for the block it was given).
    emit(blocks.map((block) => (block.id === blockId ? ({ ...block, data } as Block) : block)))
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
    reorder(payload.id, index)
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
      <div className="flex items-center justify-between gap-inline-md">
        <Text as="span" variant="caption" className="font-medium text-text-primary">
          {label}
        </Text>
        {blocks.length > 0 ? (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              setShowPreview((shown) => !shown)
            }}
          >
            {showPreview ? t('admin.blocks.preview.hide') : t('admin.blocks.preview.show')}
          </Button>
        ) : null}
      </div>

      <div
        className={isMobile ? 'flex gap-inline-sm overflow-x-auto' : 'flex flex-wrap gap-inline-sm'}
      >
        {palette.map((entry) => (
          <Button
            key={entry.type}
            variant="secondary"
            size="sm"
            className={isMobile ? 'shrink-0' : undefined}
            disabled={disabled || blocks.length >= MAX_BLOCKS_PER_DOCUMENT}
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
                  draggable={!disabled && !isMobile}
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
                  <div
                    className={
                      isMobile ? 'flex flex-col gap-stack-xs' : 'flex items-center gap-inline-sm'
                    }
                  >
                    {isMobile ? null : (
                      <span className="text-text-muted" aria-hidden="true">
                        <IconMenu size={15} />
                      </span>
                    )}
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
                    <div className="flex items-center gap-inline-sm">
                      {invalid ? (
                        <span className="font-sans text-caption text-danger">
                          {t('admin.blocks.card.invalid')}
                        </span>
                      ) : null}
                      <span className="ml-auto flex items-center gap-inline-xs">
                        <RepeaterIconButton
                          title={t('admin.blocks.moveUp')}
                          disabled={disabled || index === 0}
                          onClick={() => {
                            moveBlock(index, -1)
                          }}
                        >
                          <IconChevronUp size={15} />
                        </RepeaterIconButton>
                        <RepeaterIconButton
                          title={t('admin.blocks.moveDown')}
                          disabled={disabled || index === blocks.length - 1}
                          onClick={() => {
                            moveBlock(index, 1)
                          }}
                        >
                          <IconChevronDown size={15} />
                        </RepeaterIconButton>
                        <RepeaterIconButton
                          danger
                          title={t('common.actions.delete')}
                          disabled={disabled}
                          onClick={() => {
                            deleteBlock(block.id)
                          }}
                        >
                          <IconX size={15} />
                        </RepeaterIconButton>
                      </span>
                    </div>
                  </div>
                </Card>
              </Fragment>
            )
          })}
          {dropLine(blocks.length)}
        </div>
      )}

      {selected !== null ? (
        <InspectorPanel
          asModal={isMobile}
          titleId={inspectorTitleId}
          title={t(blockCatalogEntry(selected.type).labelKey)}
          closeLabel={t('admin.blocks.deselect')}
          onClose={() => {
            setSelectedId(null)
          }}
        >
          <BlockInspector
            block={selected}
            errorCode={validateBlock(selected)}
            disabled={disabled}
            idPrefix={`${id}-${selected.id}`}
            onChange={(data) => {
              updateData(selected.id, data)
            }}
          />
        </InspectorPanel>
      ) : null}

      {showPreview && blocks.length > 0 ? (
        <Card padding="md">
          <Stack gap="sm">
            <Text as="span" variant="caption" muted>
              {t('admin.blocks.preview.title')}
            </Text>
            <BlocksPreview documentJson={value} />
          </Stack>
        </Card>
      ) : null}
    </div>
  )
}

interface InspectorPanelProps {
  /** Render as a centered modal (mobile) instead of an inline card (desktop). */
  asModal: boolean
  titleId: string
  title: string
  closeLabel: string
  onClose: () => void
  children: ReactNode
}

/**
 * Wraps the selected block's settings form. On desktop it sits inline below the
 * board (a `Card`); at ≤820px the board has no room beside it, so the form opens
 * as a `Modal` over the card the user tapped (handoff's "card edit → modal").
 */
function InspectorPanel({
  asModal,
  titleId,
  title,
  closeLabel,
  onClose,
  children,
}: InspectorPanelProps) {
  const header = (
    <div className="flex items-center justify-between">
      <Text as="span" id={titleId} variant="caption" muted>
        {title}
      </Text>
      <button
        type="button"
        className="rounded p-1 text-text-muted hover:text-text-primary"
        title={closeLabel}
        onClick={onClose}
      >
        <IconX size={15} />
      </button>
    </div>
  )

  if (asModal) {
    return (
      <Modal
        onClose={onClose}
        closeLabel={closeLabel}
        labelledBy={titleId}
        panelClassName="max-w-md max-h-full overflow-y-auto shadow-md"
      >
        <Stack gap="sm">
          {header}
          {children}
        </Stack>
      </Modal>
    )
  }

  return (
    <Card padding="md">
      <Stack gap="sm">
        {header}
        {children}
      </Stack>
    </Card>
  )
}
