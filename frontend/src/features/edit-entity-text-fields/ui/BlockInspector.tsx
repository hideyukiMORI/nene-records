import { useState } from 'react'
import { type Media } from '@/entities/media'
import { type MessageKey, useTranslation } from '@/shared/i18n'
import { Button, Input, ResponsiveImage, Select, Stack } from '@/shared/ui'
import {
  CALLOUT_KINDS,
  HERO_VARIANTS,
  type Block,
  type BlockValidationCode,
  type CalloutBlockData,
  type CalloutKind,
  type HeroBlockData,
  type HeroMedia,
  type HeroVariant,
  type TextBlockData,
} from '@/shared/lib/blocks-document'
import { BlockMarkdownInput } from './BlockMarkdownInput'
import { MediaSelectorModal } from './MediaSelectorModal'

interface BlockInspectorProps {
  block: Block
  errorCode: BlockValidationCode | null
  disabled: boolean
  idPrefix: string
  onChange: (data: TextBlockData | CalloutBlockData | HeroBlockData) => void
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

/** Settings form for the selected block (text / callout / hero). */
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
