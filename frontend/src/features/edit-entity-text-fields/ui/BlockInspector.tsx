import { type MessageKey, useTranslation } from '@/shared/i18n'
import { Input, Select, Stack } from '@/shared/ui'
import {
  CALLOUT_KINDS,
  HERO_VARIANTS,
  type Block,
  type BlockValidationCode,
  type CalloutBlockData,
  type CalloutKind,
  type HeroBlockData,
  type HeroVariant,
  type TextBlockData,
} from '@/shared/lib/blocks-document'
import { BlockMarkdownInput } from './BlockMarkdownInput'

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
    </Stack>
  )
}
