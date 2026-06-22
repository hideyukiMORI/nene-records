import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'
import { PublicMarkdownContent } from '@/shared/ui/markdown'

interface BlockMarkdownInputProps {
  id: string
  label: string
  value: string
  disabled: boolean
  error?: string
  onChange: (value: string) => void
}

/**
 * Compact Markdown editor for the block inspector: textarea + write/preview tabs.
 * Preview reuses the sanitizing {@link PublicMarkdownContent} (no raw HTML).
 * Self-contained so the block editor stays within its own feature (FSD).
 */
export function BlockMarkdownInput({
  id,
  label,
  value,
  disabled,
  error,
  onChange,
}: BlockMarkdownInputProps) {
  const { t } = useTranslation()
  const [tab, setTab] = useState<'write' | 'preview'>('write')

  const tabClass = (active: boolean) =>
    [
      'pb-1.5 font-sans text-caption font-medium transition-colors focus-visible:outline-none',
      active
        ? 'border-b-2 border-accent text-text-primary'
        : 'text-text-muted hover:text-text-primary',
    ].join(' ')

  return (
    <div className="flex flex-col gap-stack-xs">
      <label htmlFor={id} className="font-sans text-caption font-medium text-text-primary">
        {label}
      </label>

      <div
        className={[
          'rounded-md border shadow-sm',
          error !== undefined ? 'border-danger' : 'border-border',
        ].join(' ')}
      >
        <div className="flex items-center gap-inline-md border-b border-border px-inline-sm">
          <button
            type="button"
            className={tabClass(tab === 'write')}
            onClick={() => {
              setTab('write')
            }}
          >
            {t('admin.markdownEditor.write')}
          </button>
          <button
            type="button"
            className={tabClass(tab === 'preview')}
            onClick={() => {
              setTab('preview')
            }}
          >
            {t('admin.markdownEditor.preview')}
          </button>
        </div>

        {tab === 'write' ? (
          <textarea
            id={id}
            value={value}
            disabled={disabled}
            rows={8}
            onChange={(event) => {
              onChange(event.target.value)
            }}
            className="w-full rounded-b-md bg-surface-raised px-inline-md py-stack-sm font-mono text-caption text-text-primary focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
          />
        ) : (
          <div className="min-h-32 rounded-b-md bg-surface-raised px-inline-md py-stack-sm">
            {value.trim() === '' ? (
              <Text muted>{t('admin.markdownEditor.empty')}</Text>
            ) : (
              <PublicMarkdownContent markdown={value} />
            )}
          </div>
        )}
      </div>

      {error !== undefined ? (
        <span className="font-sans text-caption text-danger">{error}</span>
      ) : null}
    </div>
  )
}
