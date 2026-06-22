import { type MessageKey, useTranslation } from '@/shared/i18n'
import { Input, Select, Stack } from '@/shared/ui'
import {
  CALLOUT_KINDS,
  type Block,
  type BlockValidationCode,
  type CalloutBlockData,
  type CalloutKind,
  type TextBlockData,
} from '@/shared/lib/blocks-document'
import { BlockMarkdownInput } from './BlockMarkdownInput'

interface BlockInspectorProps {
  block: Block
  errorCode: BlockValidationCode | null
  disabled: boolean
  idPrefix: string
  onChange: (data: TextBlockData | CalloutBlockData) => void
}

const KIND_LABEL_KEY: Record<CalloutKind, MessageKey> = {
  info: 'admin.blocks.kind.info',
  warn: 'admin.blocks.kind.warn',
  ok: 'admin.blocks.kind.ok',
  danger: 'admin.blocks.kind.danger',
}

/** Settings form for the selected block (text / callout). */
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
