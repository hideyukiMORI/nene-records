import { useState } from 'react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

interface MarkdownFieldInputProps {
  id: string
  label: string
  value: string
  disabled: boolean
  onChange: (value: string) => void
}

export function MarkdownFieldInput({
  id,
  label,
  value,
  disabled,
  onChange,
}: MarkdownFieldInputProps) {
  const { t } = useTranslation()
  const [tab, setTab] = useState<'write' | 'preview'>('write')

  return (
    <Stack gap="xs">
      <label htmlFor={id} className="font-sans text-body font-medium text-text-primary">
        {label}
      </label>

      {/* Tab bar */}
      <div className="flex gap-inline-sm border-b border-border">
        <button
          type="button"
          onClick={() => {
            setTab('write')
          }}
          className={`pb-1 text-sm font-medium transition-colors focus-visible:outline-none ${
            tab === 'write'
              ? 'border-b-2 border-blue-500 text-text-primary'
              : 'text-text-muted hover:text-text-primary'
          }`}
        >
          {t('admin.markdownEditor.write')}
        </button>
        <button
          type="button"
          onClick={() => {
            setTab('preview')
          }}
          className={`pb-1 text-sm font-medium transition-colors focus-visible:outline-none ${
            tab === 'preview'
              ? 'border-b-2 border-blue-500 text-text-primary'
              : 'text-text-muted hover:text-text-primary'
          }`}
        >
          {t('admin.markdownEditor.preview')}
        </button>
      </div>

      {/* Write pane */}
      {tab === 'write' && (
        <textarea
          id={id}
          value={value}
          disabled={disabled}
          rows={10}
          onChange={(e) => {
            onChange(e.target.value)
          }}
          className="w-full rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-mono text-sm text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50"
        />
      )}

      {/* Preview pane */}
      {tab === 'preview' && (
        <div className="min-h-40 rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm">
          {value.trim() === '' ? (
            <Text muted>{t('admin.markdownEditor.empty')}</Text>
          ) : (
            <div className="prose prose-sm max-w-none text-text-primary">
              <ReactMarkdown remarkPlugins={[remarkGfm]}>{value}</ReactMarkdown>
            </div>
          )}
        </div>
      )}
    </Stack>
  )
}
