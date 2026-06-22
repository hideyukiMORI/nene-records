import { useState } from 'react'
import { type Media } from '@/entities/media'
import { type MessageKey, useTranslation } from '@/shared/i18n'
import { Button, Input, ResponsiveImage, Select, Stack } from '@/shared/ui'
import { IconChevronDown, IconChevronUp, IconX } from '@/shared/ui/icons/Icons'
import {
  CALLOUT_KINDS,
  CHART_TYPES,
  GALLERY_LAYOUTS,
  GROUP_TONES,
  HERO_VARIANTS,
  createBlock,
  validateBlock,
  type Block,
  type BlockType,
  type BlockValidationCode,
  type CalloutBlockData,
  type CalloutKind,
  type ChartBlockData,
  type ChartType,
  type GalleryBlockData,
  type GalleryItem,
  type GalleryLayout,
  type GroupBlockData,
  type GroupTone,
  type HeroBlockData,
  type HeroMedia,
  type HeroVariant,
  type LeafBlock,
  type SeriesPoint,
  type TextBlockData,
} from '@/shared/lib/blocks-document'
import { BLOCK_CATALOG, blockCatalogEntry } from './block-catalog'
import { BlockMarkdownInput } from './BlockMarkdownInput'
import { MediaSelectorModal } from './MediaSelectorModal'

type BlockDataChange =
  | TextBlockData
  | CalloutBlockData
  | HeroBlockData
  | GalleryBlockData
  | ChartBlockData
  | GroupBlockData

/** Leaf block types a group may contain (no container-in-container; depth 2). */
const GROUP_CHILD_CATALOG = BLOCK_CATALOG.filter((entry) => entry.type !== 'group')

interface BlockInspectorProps {
  block: Block
  errorCode: BlockValidationCode | null
  disabled: boolean
  idPrefix: string
  onChange: (data: BlockDataChange) => void
}

const KIND_LABEL_KEY: Record<CalloutKind, MessageKey> = {
  info: 'admin.blocks.kind.info',
  warn: 'admin.blocks.kind.warn',
  ok: 'admin.blocks.kind.ok',
  danger: 'admin.blocks.kind.danger',
}

const VARIANT_LABEL_KEY: Record<HeroVariant, MessageKey> = {
  standard: 'admin.blocks.heroVariant.standard',
  minimal: 'admin.blocks.heroVariant.minimal',
  fullbleed: 'admin.blocks.heroVariant.fullbleed',
}

const LAYOUT_LABEL_KEY: Record<GalleryLayout, MessageKey> = {
  carousel: 'admin.blocks.galleryLayout.carousel',
  grid: 'admin.blocks.galleryLayout.grid',
}

const CHART_TYPE_LABEL_KEY: Record<ChartType, MessageKey> = {
  bar: 'admin.blocks.chartType.bar',
  line: 'admin.blocks.chartType.line',
}

const GROUP_TONE_LABEL_KEY: Record<GroupTone, MessageKey> = {
  plain: 'admin.blocks.groupTone.plain',
  muted: 'admin.blocks.groupTone.muted',
  card: 'admin.blocks.groupTone.card',
}

/** Settings form for the selected block (text / callout / hero / gallery). */
export function BlockInspector({
  block,
  errorCode,
  disabled,
  idPrefix,
  onChange,
}: BlockInspectorProps) {
  const { t } = useTranslation()

  if (block.type === 'text') {
    return (
      <BlockMarkdownInput
        id={`${idPrefix}-markdown`}
        label={t('admin.blocks.field.body')}
        value={block.data.markdown}
        disabled={disabled}
        error={errorCode === 'markdown-required' ? t('admin.blocks.error.bodyRequired') : undefined}
        onChange={(markdown) => {
          onChange({ markdown })
        }}
      />
    )
  }

  if (block.type === 'callout') {
    const data = block.data
    return (
      <Stack gap="sm">
        <Select
          id={`${idPrefix}-kind`}
          label={t('admin.blocks.field.kind')}
          value={data.kind}
          disabled={disabled}
          onChange={(event) => {
            onChange({ ...data, kind: event.target.value as CalloutKind })
          }}
        >
          {CALLOUT_KINDS.map((kind) => (
            <option key={kind} value={kind}>
              {t(KIND_LABEL_KEY[kind])}
            </option>
          ))}
        </Select>
        <Input
          id={`${idPrefix}-title`}
          label={t('admin.blocks.field.title')}
          value={data.title ?? ''}
          disabled={disabled}
          autoComplete="off"
          onChange={(event) => {
            onChange({ ...data, title: event.target.value })
          }}
        />
        <BlockMarkdownInput
          id={`${idPrefix}-body`}
          label={t('admin.blocks.field.body')}
          value={data.body}
          disabled={disabled}
          error={errorCode === 'body-required' ? t('admin.blocks.error.bodyRequired') : undefined}
          onChange={(body) => {
            onChange({ ...data, body })
          }}
        />
      </Stack>
    )
  }

  if (block.type === 'gallery') {
    const data = block.data
    return (
      <Stack gap="sm">
        <Select
          id={`${idPrefix}-layout`}
          label={t('admin.blocks.field.layout')}
          value={data.layout}
          disabled={disabled}
          onChange={(event) => {
            onChange({ ...data, layout: event.target.value as GalleryLayout })
          }}
        >
          {GALLERY_LAYOUTS.map((layout) => (
            <option key={layout} value={layout}>
              {t(LAYOUT_LABEL_KEY[layout])}
            </option>
          ))}
        </Select>
        <GalleryItemsField
          idPrefix={idPrefix}
          items={data.items}
          disabled={disabled}
          error={errorCode === 'items-required' ? t('admin.blocks.error.itemsRequired') : undefined}
          onChange={(items) => {
            onChange({ ...data, items })
          }}
        />
      </Stack>
    )
  }

  if (block.type === 'chart') {
    const data = block.data
    return (
      <Stack gap="sm">
        <Select
          id={`${idPrefix}-chart-type`}
          label={t('admin.blocks.field.chartType')}
          value={data.chartType}
          disabled={disabled}
          onChange={(event) => {
            onChange({ ...data, chartType: event.target.value as ChartType })
          }}
        >
          {CHART_TYPES.map((chartType) => (
            <option key={chartType} value={chartType}>
              {t(CHART_TYPE_LABEL_KEY[chartType])}
            </option>
          ))}
        </Select>
        <Input
          id={`${idPrefix}-chart-title`}
          label={t('admin.blocks.field.title')}
          value={data.title ?? ''}
          disabled={disabled}
          autoComplete="off"
          onChange={(event) => {
            onChange({ ...data, title: event.target.value })
          }}
        />
        <SeriesField
          idPrefix={idPrefix}
          series={data.series}
          disabled={disabled}
          error={
            errorCode === 'series-required' ? t('admin.blocks.error.seriesRequired') : undefined
          }
          onChange={(series) => {
            onChange({ ...data, series })
          }}
        />
        <Input
          id={`${idPrefix}-chart-summary`}
          label={t('admin.blocks.field.summary')}
          value={data.summary}
          disabled={disabled}
          autoComplete="off"
          error={
            errorCode === 'summary-required' ? t('admin.blocks.error.summaryRequired') : undefined
          }
          onChange={(event) => {
            onChange({ ...data, summary: event.target.value })
          }}
        />
      </Stack>
    )
  }

  if (block.type === 'group') {
    const data = block.data
    return (
      <Stack gap="sm">
        <Select
          id={`${idPrefix}-group-tone`}
          label={t('admin.blocks.field.tone')}
          value={data.tone}
          disabled={disabled}
          onChange={(event) => {
            onChange({ ...data, tone: event.target.value as GroupTone })
          }}
        >
          {GROUP_TONES.map((tone) => (
            <option key={tone} value={tone}>
              {t(GROUP_TONE_LABEL_KEY[tone])}
            </option>
          ))}
        </Select>
        <GroupChildrenField
          idPrefix={idPrefix}
          items={data.children}
          disabled={disabled}
          error={
            errorCode === 'children-required' ? t('admin.blocks.error.childrenRequired') : undefined
          }
          onChange={(children) => {
            onChange({ ...data, children })
          }}
        />
      </Stack>
    )
  }

  const data = block.data
  return (
    <Stack gap="sm">
      <Select
        id={`${idPrefix}-variant`}
        label={t('admin.blocks.field.variant')}
        value={data.variant}
        disabled={disabled}
        onChange={(event) => {
          onChange({ ...data, variant: event.target.value as HeroVariant })
        }}
      >
        {HERO_VARIANTS.map((variant) => (
          <option key={variant} value={variant}>
            {t(VARIANT_LABEL_KEY[variant])}
          </option>
        ))}
      </Select>
      <Input
        id={`${idPrefix}-kicker`}
        label={t('admin.blocks.field.kicker')}
        value={data.kicker ?? ''}
        disabled={disabled}
        autoComplete="off"
        onChange={(event) => {
          onChange({ ...data, kicker: event.target.value })
        }}
      />
      <Input
        id={`${idPrefix}-heading`}
        label={t('admin.blocks.field.heading')}
        value={data.heading}
        disabled={disabled}
        autoComplete="off"
        error={
          errorCode === 'heading-required' ? t('admin.blocks.error.headingRequired') : undefined
        }
        onChange={(event) => {
          onChange({ ...data, heading: event.target.value })
        }}
      />
      <Input
        id={`${idPrefix}-lead`}
        label={t('admin.blocks.field.lead')}
        value={data.lead ?? ''}
        disabled={disabled}
        autoComplete="off"
        onChange={(event) => {
          onChange({ ...data, lead: event.target.value })
        }}
      />
      <Input
        id={`${idPrefix}-cta-label`}
        label={t('admin.blocks.field.ctaLabel')}
        value={data.ctaLabel ?? ''}
        disabled={disabled}
        autoComplete="off"
        onChange={(event) => {
          onChange({ ...data, ctaLabel: event.target.value })
        }}
      />
      <Input
        id={`${idPrefix}-cta-url`}
        label={t('admin.blocks.field.ctaUrl')}
        value={data.ctaUrl ?? ''}
        disabled={disabled}
        autoComplete="off"
        onChange={(event) => {
          onChange({ ...data, ctaUrl: event.target.value })
        }}
      />
      <Input
        id={`${idPrefix}-ghost-label`}
        label={t('admin.blocks.field.ghostLabel')}
        value={data.ghostLabel ?? ''}
        disabled={disabled}
        autoComplete="off"
        onChange={(event) => {
          onChange({ ...data, ghostLabel: event.target.value })
        }}
      />
      <Input
        id={`${idPrefix}-ghost-url`}
        label={t('admin.blocks.field.ghostUrl')}
        value={data.ghostUrl ?? ''}
        disabled={disabled}
        autoComplete="off"
        onChange={(event) => {
          onChange({ ...data, ghostUrl: event.target.value })
        }}
      />
      <HeroMediaField
        idPrefix={idPrefix}
        media={data.media}
        disabled={disabled}
        onChange={(media) => {
          if (media === undefined) {
            const next = { ...data }
            delete next.media
            onChange(next)
            return
          }
          onChange({ ...data, media })
        }}
      />
    </Stack>
  )
}

interface HeroMediaFieldProps {
  idPrefix: string
  media: HeroMedia | undefined
  disabled: boolean
  onChange: (media: HeroMedia | undefined) => void
}

/** Library image picker + alt text for the hero art (#486 S3). */
function HeroMediaField({ idPrefix, media, disabled, onChange }: HeroMediaFieldProps) {
  const { t } = useTranslation()
  const [pickerOpen, setPickerOpen] = useState(false)

  const handleSelect = (item: Media) => {
    onChange({
      mediaId: String(item.id),
      url: item.url,
      ...(item.altText !== null && item.altText !== '' ? { alt: item.altText } : {}),
    })
    setPickerOpen(false)
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <span className="font-sans text-caption font-medium text-text-primary">
        {t('admin.blocks.field.image')}
      </span>
      <div className="flex items-center gap-inline-sm">
        {media !== undefined ? (
          <ResponsiveImage
            src={media.url}
            alt={media.alt ?? ''}
            sizes="96px"
            className="h-16 w-24 rounded border border-border object-cover"
          />
        ) : (
          <span className="font-sans text-caption text-text-muted">
            {t('admin.blocks.media.none')}
          </span>
        )}
        <Button
          variant="secondary"
          size="sm"
          disabled={disabled}
          onClick={() => {
            setPickerOpen(true)
          }}
        >
          {media !== undefined ? t('admin.blocks.media.change') : t('admin.blocks.media.select')}
        </Button>
        {media !== undefined ? (
          <Button
            variant="ghost"
            size="sm"
            disabled={disabled}
            onClick={() => {
              onChange(undefined)
            }}
          >
            {t('admin.blocks.media.remove')}
          </Button>
        ) : null}
      </div>
      {media !== undefined ? (
        <Input
          id={`${idPrefix}-media-alt`}
          label={t('admin.blocks.media.alt')}
          value={media.alt ?? ''}
          disabled={disabled}
          autoComplete="off"
          onChange={(event) => {
            onChange({ ...media, alt: event.target.value })
          }}
        />
      ) : null}
      {pickerOpen ? (
        <MediaSelectorModal
          currentMediaId={media?.mediaId ?? null}
          onSelect={handleSelect}
          onClose={() => {
            setPickerOpen(false)
          }}
        />
      ) : null}
    </div>
  )
}

interface GalleryItemsFieldProps {
  idPrefix: string
  items: GalleryItem[]
  disabled: boolean
  error?: string | undefined
  onChange: (items: GalleryItem[]) => void
}

/** Repeater of gallery slides — each a library image + required alt (C4) + caption (#486 S4). */
function GalleryItemsField({ idPrefix, items, disabled, error, onChange }: GalleryItemsFieldProps) {
  const { t } = useTranslation()
  // null = closed, -1 = adding a new slide, >= 0 = replacing slide i's image
  const [pickFor, setPickFor] = useState<number | null>(null)

  const update = (index: number, patch: Partial<GalleryItem>) => {
    onChange(items.map((item, i) => (i === index ? { ...item, ...patch } : item)))
  }
  const move = (index: number, direction: -1 | 1) => {
    const target = index + direction
    if (target < 0 || target >= items.length) {
      return
    }
    const next = items.slice()
    const [moved] = next.splice(index, 1)
    if (moved === undefined) {
      return
    }
    next.splice(target, 0, moved)
    onChange(next)
  }
  const remove = (index: number) => {
    onChange(items.filter((_, i) => i !== index))
  }
  const handleSelect = (media: Media) => {
    if (pickFor === -1) {
      onChange([...items, { mediaId: String(media.id), url: media.url, alt: media.altText ?? '' }])
    } else if (pickFor !== null) {
      update(pickFor, { mediaId: String(media.id), url: media.url })
    }
    setPickFor(null)
  }

  return (
    <div className="flex flex-col gap-stack-sm">
      <span className="font-sans text-caption font-medium text-text-primary">
        {t('admin.blocks.field.images')}
      </span>
      {error !== undefined ? (
        <span role="alert" className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
      {items.map((item, index) => (
        <div
          key={`${item.mediaId}-${String(index)}`}
          className="flex flex-col gap-stack-xs rounded-md border border-border p-inline-sm"
        >
          <div className="flex items-center gap-inline-sm">
            <ResponsiveImage
              src={item.url}
              alt={item.alt}
              sizes="80px"
              className="h-12 w-16 rounded border border-border object-cover"
            />
            <Button
              variant="secondary"
              size="sm"
              disabled={disabled}
              onClick={() => {
                setPickFor(index)
              }}
            >
              {t('admin.blocks.media.change')}
            </Button>
            <span className="flex-1" />
            <button
              type="button"
              className="rounded p-1 text-text-muted hover:text-text-primary disabled:opacity-40"
              title={t('admin.blocks.moveUp')}
              disabled={disabled || index === 0}
              onClick={() => {
                move(index, -1)
              }}
            >
              <IconChevronUp size={15} />
            </button>
            <button
              type="button"
              className="rounded p-1 text-text-muted hover:text-text-primary disabled:opacity-40"
              title={t('admin.blocks.moveDown')}
              disabled={disabled || index === items.length - 1}
              onClick={() => {
                move(index, 1)
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
                remove(index)
              }}
            >
              <IconX size={15} />
            </button>
          </div>
          <Input
            id={`${idPrefix}-item-${String(index)}-alt`}
            label={t('admin.blocks.media.alt')}
            value={item.alt}
            disabled={disabled}
            autoComplete="off"
            error={item.alt.trim() === '' ? t('admin.blocks.error.altRequired') : undefined}
            onChange={(event) => {
              update(index, { alt: event.target.value })
            }}
          />
          <Input
            id={`${idPrefix}-item-${String(index)}-caption`}
            label={t('admin.blocks.media.caption')}
            value={item.caption ?? ''}
            disabled={disabled}
            autoComplete="off"
            onChange={(event) => {
              update(index, { caption: event.target.value })
            }}
          />
        </div>
      ))}
      <div>
        <Button
          variant="secondary"
          size="sm"
          disabled={disabled}
          onClick={() => {
            setPickFor(-1)
          }}
        >
          {t('admin.blocks.media.addImage')}
        </Button>
      </div>
      {pickFor !== null ? (
        <MediaSelectorModal
          currentMediaId={pickFor >= 0 ? (items[pickFor]?.mediaId ?? null) : null}
          onSelect={handleSelect}
          onClose={() => {
            setPickFor(null)
          }}
        />
      ) : null}
    </div>
  )
}

interface SeriesFieldProps {
  idPrefix: string
  series: SeriesPoint[]
  disabled: boolean
  error?: string | undefined
  onChange: (series: SeriesPoint[]) => void
}

/** Repeater of chart data points — label + numeric value (#486 S5). */
function SeriesField({ idPrefix, series, disabled, error, onChange }: SeriesFieldProps) {
  const { t } = useTranslation()

  const update = (index: number, patch: Partial<SeriesPoint>) => {
    onChange(series.map((point, i) => (i === index ? { ...point, ...patch } : point)))
  }
  const move = (index: number, direction: -1 | 1) => {
    const target = index + direction
    if (target < 0 || target >= series.length) {
      return
    }
    const next = series.slice()
    const [moved] = next.splice(index, 1)
    if (moved === undefined) {
      return
    }
    next.splice(target, 0, moved)
    onChange(next)
  }
  const remove = (index: number) => {
    onChange(series.filter((_, i) => i !== index))
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <span className="font-sans text-caption font-medium text-text-primary">
        {t('admin.blocks.field.series')}
      </span>
      {error !== undefined ? (
        <span role="alert" className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
      {series.map((point, index) => (
        <div key={index} className="flex items-end gap-inline-sm">
          <div className="flex-1">
            <Input
              id={`${idPrefix}-series-${String(index)}-label`}
              label={t('admin.blocks.series.label')}
              value={point.label}
              disabled={disabled}
              autoComplete="off"
              error={point.label.trim() === '' ? t('admin.blocks.error.labelRequired') : undefined}
              onChange={(event) => {
                update(index, { label: event.target.value })
              }}
            />
          </div>
          <div className="w-24">
            <Input
              id={`${idPrefix}-series-${String(index)}-value`}
              label={t('admin.blocks.series.value')}
              type="number"
              value={String(point.value)}
              disabled={disabled}
              onChange={(event) => {
                const next = Number(event.target.value)
                update(index, { value: Number.isFinite(next) ? next : 0 })
              }}
            />
          </div>
          <button
            type="button"
            className="rounded p-1 text-text-muted hover:text-text-primary disabled:opacity-40"
            title={t('admin.blocks.moveUp')}
            disabled={disabled || index === 0}
            onClick={() => {
              move(index, -1)
            }}
          >
            <IconChevronUp size={15} />
          </button>
          <button
            type="button"
            className="rounded p-1 text-text-muted hover:text-text-primary disabled:opacity-40"
            title={t('admin.blocks.moveDown')}
            disabled={disabled || index === series.length - 1}
            onClick={() => {
              move(index, 1)
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
              remove(index)
            }}
          >
            <IconX size={15} />
          </button>
        </div>
      ))}
      <div>
        <Button
          variant="secondary"
          size="sm"
          disabled={disabled}
          onClick={() => {
            onChange([...series, { label: '', value: 0 }])
          }}
        >
          {t('admin.blocks.series.add')}
        </Button>
      </div>
    </div>
  )
}

interface GroupChildrenFieldProps {
  idPrefix: string
  items: LeafBlock[]
  disabled: boolean
  error?: string | undefined
  onChange: (items: LeafBlock[]) => void
}

/**
 * Sub-editor for a group's child blocks (#491 WS2): a leaf-only palette + a
 * reorderable list where each child expands to its own BlockInspector. Children
 * are leaf blocks, so the recursion terminates at one level (depth 2).
 */
function GroupChildrenField({
  idPrefix,
  items,
  disabled,
  error,
  onChange,
}: GroupChildrenFieldProps) {
  const { t } = useTranslation()
  const [openIndex, setOpenIndex] = useState<number | null>(null)

  const add = (type: BlockType) => {
    onChange([...items, createBlock(type) as LeafBlock])
    setOpenIndex(items.length)
  }
  const update = (index: number, data: BlockDataChange) => {
    onChange(items.map((item, i) => (i === index ? ({ ...item, data } as LeafBlock) : item)))
  }
  const move = (index: number, direction: -1 | 1) => {
    const target = index + direction
    if (target < 0 || target >= items.length) {
      return
    }
    const next = items.slice()
    const [moved] = next.splice(index, 1)
    if (moved === undefined) {
      return
    }
    next.splice(target, 0, moved)
    onChange(next)
    setOpenIndex(null)
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
            disabled={disabled}
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
