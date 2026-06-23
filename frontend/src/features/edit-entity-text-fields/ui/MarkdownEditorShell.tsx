import { type ReactNode, useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'
import { PublicMarkdownContent } from '@/shared/ui/markdown'

interface MarkdownEditorShellProps {
  id: string
  label: string
  value: string
  error?: string | undefined
  /** `compact` for the block inspector, `comfortable` for the full field editor. */
  size?: 'compact' | 'comfortable'
  /** The write-mode pane (a textarea, optionally preceded by a toolbar). */
  children: ReactNode
}

const tabClass = (active: boolean) =>
  [
    'pb-1.5 font-sans text-caption font-medium transition-colors focus-visible:outline-none',
    active
      ? 'border-b-2 border-accent text-text-primary'
      : 'text-text-muted hover:text-text-primary',
  ].join(' ')

/**
 * Shared write/preview chrome for the Markdown editors (tab bar, preview pane,
 * bordered container). The caller supplies the write pane as `children`; the
 * preview renders the sanitizing {@link PublicMarkdownContent}.
 */
export function MarkdownEditorShell({
  id,
  label,
  value,
  error,
  size = 'comfortable',
  children,
}: MarkdownEditorShellProps) {
  const { t } = useTranslation()
  const [tab, setTab] = useState<'write' | 'preview'>('write')
  const labelClass =
    size === 'compact'
      ? 'font-sans text-caption font-medium text-text-primary'
      : 'font-sans text-body font-medium text-text-primary'
  const previewMinHeight = size === 'compact' ? 'min-h-32' : 'min-h-64'

  return (
    <div className="flex flex-col gap-stack-xs">
      <label htmlFor={id} className={labelClass}>
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
          children
        ) : (
          <div
            className={`${previewMinHeight} rounded-b-md bg-surface-raised px-inline-md py-stack-sm`}
          >
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
