import { type ReactElement } from 'react'
import { type MessageKey, useTranslation } from '@/shared/i18n'
import { Button, Input, Stack } from '@/shared/ui'
import {
  CALLOUT_KINDS,
  CHART_TYPES,
  GALLERY_LAYOUTS,
  GROUP_TONES,
  HERO_VARIANTS,
  MAX_COLUMN_CHILDREN,
  MAX_GROUP_CHILDREN,
  SPACER_SIZES,
  type Block,
  type BlockData,
  type BlockValidationCode,
  type CalloutKind,
  type ChartType,
  type ColumnsBlockData,
  type GalleryLayout,
  type GroupTone,
  type HeroVariant,
  type SpacerSize,
} from '@/shared/lib/blocks-document'
import { BlockMarkdownInput } from './BlockMarkdownInput'
import { EnumSelect } from './inspectors/EnumSelect'
import { GalleryItemsField } from './inspectors/GalleryItemsField'
import { GroupChildrenField } from './inspectors/GroupChildrenField'
import { HeroMediaField } from './inspectors/HeroMediaField'
import { SeriesField } from './inspectors/SeriesField'

interface BlockInspectorProps {
  block: Block
  errorCode: BlockValidationCode | null
  disabled: boolean
  idPrefix: string
  onChange: (data: BlockData) => void
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

const SPACER_SIZE_LABEL_KEY: Record<SpacerSize, MessageKey> = {
  sm: 'admin.blocks.spacerSize.sm',
  md: 'admin.blocks.spacerSize.md',
  lg: 'admin.blocks.spacerSize.lg',
}

/**
 * Settings form for the selected block. Exhaustive `switch` on `block.type` — the
 * `ReactElement` return annotation makes an unhandled block type a compile error,
 * so adding a block type can't silently fall through to the hero form.
 */
export function BlockInspector({
  block,
  errorCode,
  disabled,
  idPrefix,
  onChange,
}: BlockInspectorProps): ReactElement {
  const { t } = useTranslation()

  switch (block.type) {
    case 'text':
      return (
        <BlockMarkdownInput
          id={`${idPrefix}-markdown`}
          label={t('admin.blocks.field.body')}
          value={block.data.markdown}
          disabled={disabled}
          error={
            errorCode === 'markdown-required' ? t('admin.blocks.error.bodyRequired') : undefined
          }
          onChange={(markdown) => {
            onChange({ markdown })
          }}
        />
      )

    case 'callout': {
      const data = block.data
      return (
        <Stack gap="sm">
          <EnumSelect
            id={`${idPrefix}-kind`}
            label={t('admin.blocks.field.kind')}
            value={data.kind}
            options={CALLOUT_KINDS}
            labelKeys={KIND_LABEL_KEY}
            disabled={disabled}
            onChange={(kind) => {
              onChange({ ...data, kind })
            }}
          />
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

    case 'gallery': {
      const data = block.data
      return (
        <Stack gap="sm">
          <EnumSelect
            id={`${idPrefix}-layout`}
            label={t('admin.blocks.field.layout')}
            value={data.layout}
            options={GALLERY_LAYOUTS}
            labelKeys={LAYOUT_LABEL_KEY}
            disabled={disabled}
            onChange={(layout) => {
              onChange({ ...data, layout })
            }}
          />
          <GalleryItemsField
            idPrefix={idPrefix}
            items={data.items}
            disabled={disabled}
            error={
              errorCode === 'items-required' ? t('admin.blocks.error.itemsRequired') : undefined
            }
            onChange={(items) => {
              onChange({ ...data, items })
            }}
          />
        </Stack>
      )
    }

    case 'chart': {
      const data = block.data
      return (
        <Stack gap="sm">
          <EnumSelect
            id={`${idPrefix}-chart-type`}
            label={t('admin.blocks.field.chartType')}
            value={data.chartType}
            options={CHART_TYPES}
            labelKeys={CHART_TYPE_LABEL_KEY}
            disabled={disabled}
            onChange={(chartType) => {
              onChange({ ...data, chartType })
            }}
          />
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

    case 'group': {
      const data = block.data
      return (
        <Stack gap="sm">
          <EnumSelect
            id={`${idPrefix}-group-tone`}
            label={t('admin.blocks.field.tone')}
            value={data.tone}
            options={GROUP_TONES}
            labelKeys={GROUP_TONE_LABEL_KEY}
            disabled={disabled}
            onChange={(tone) => {
              onChange({ ...data, tone })
            }}
          />
          <GroupChildrenField
            idPrefix={idPrefix}
            items={data.children}
            disabled={disabled}
            maxItems={MAX_GROUP_CHILDREN}
            error={
              errorCode === 'children-required'
                ? t('admin.blocks.error.childrenRequired')
                : errorCode === 'children-invalid'
                  ? t('admin.blocks.error.childrenInvalid')
                  : undefined
            }
            onChange={(children) => {
              onChange({ ...data, children })
            }}
          />
        </Stack>
      )
    }

    case 'columns': {
      const data = block.data
      const setColumns = (columns: ColumnsBlockData['columns']) => {
        onChange({ ...data, columns })
      }
      return (
        <Stack gap="sm">
          <div className="flex items-center gap-inline-sm">
            <span className="flex-1 font-sans text-caption font-medium text-text-primary">
              {t('admin.blocks.columns.count', { count: String(data.columns.length) })}
            </span>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={disabled || data.columns.length <= 2}
              onClick={() => {
                setColumns(data.columns.slice(0, -1))
              }}
            >
              {t('admin.blocks.columns.remove')}
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={disabled || data.columns.length >= 4}
              onClick={() => {
                setColumns([...data.columns, { children: [] }])
              }}
            >
              {t('admin.blocks.columns.add')}
            </Button>
          </div>
          {errorCode === 'children-required' || errorCode === 'children-invalid' ? (
            <span role="alert" className="font-sans text-caption text-danger">
              {errorCode === 'children-required'
                ? t('admin.blocks.error.childrenRequired')
                : t('admin.blocks.error.childrenInvalid')}
            </span>
          ) : null}
          {data.columns.map((column, columnIndex) => (
            <div
              key={columnIndex}
              className="flex flex-col gap-stack-xs rounded-md border border-border p-inline-sm"
            >
              <span className="font-sans text-caption font-medium text-text-muted">
                {t('admin.blocks.columns.label', { n: String(columnIndex + 1) })}
              </span>
              <GroupChildrenField
                idPrefix={`${idPrefix}-col${String(columnIndex)}`}
                items={column.children}
                disabled={disabled}
                maxItems={MAX_COLUMN_CHILDREN}
                onChange={(children) => {
                  setColumns(data.columns.map((col, i) => (i === columnIndex ? { children } : col)))
                }}
              />
            </div>
          ))}
        </Stack>
      )
    }

    case 'spacer': {
      const data = block.data
      return (
        <EnumSelect
          id={`${idPrefix}-spacer-size`}
          label={t('admin.blocks.field.spacerSize')}
          value={data.size}
          options={SPACER_SIZES}
          labelKeys={SPACER_SIZE_LABEL_KEY}
          disabled={disabled}
          onChange={(size) => {
            onChange({ size })
          }}
        />
      )
    }

    case 'divider':
      return (
        <span className="font-sans text-caption text-text-muted">
          {t('admin.blocks.divider.hint')}
        </span>
      )

    case 'hero': {
      const data = block.data
      return (
        <Stack gap="sm">
          <EnumSelect
            id={`${idPrefix}-variant`}
            label={t('admin.blocks.field.variant')}
            value={data.variant}
            options={HERO_VARIANTS}
            labelKeys={VARIANT_LABEL_KEY}
            disabled={disabled}
            onChange={(variant) => {
              onChange({ ...data, variant })
            }}
          />
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
  }
}
