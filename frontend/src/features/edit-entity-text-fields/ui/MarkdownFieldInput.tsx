import { useCallback, useRef, useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'
import { PublicMarkdownContent } from '@/shared/ui/markdown/PublicMarkdownContent'

interface MarkdownFieldInputProps {
  id: string
  label: string
  value: string
  disabled: boolean
  onChange: (value: string) => void
}

interface ToolbarAction {
  label: string
  icon: string
  action: (value: string, selStart: number, selEnd: number) => { text: string; cursor: number }
}

const TOOLBAR_ACTIONS: ToolbarAction[] = [
  {
    label: 'Bold',
    icon: 'B',
    action: (v, s, e) => {
      const sel = v.slice(s, e) || 'テキスト'
      return { text: v.slice(0, s) + `**${sel}**` + v.slice(e), cursor: s + sel.length + 4 }
    },
  },
  {
    label: 'Italic',
    icon: 'I',
    action: (v, s, e) => {
      const sel = v.slice(s, e) || 'テキスト'
      return { text: v.slice(0, s) + `*${sel}*` + v.slice(e), cursor: s + sel.length + 2 }
    },
  },
  {
    label: 'H2',
    icon: 'H2',
    action: (v, s, e) => {
      const lineStart = v.lastIndexOf('\n', s - 1) + 1
      const line = v.slice(lineStart, e) || 'Heading'
      const before = v.slice(0, lineStart)
      const after = v.slice(e)
      const heading = line.startsWith('## ') ? line.slice(3) : `## ${line.replace(/^#+\s*/, '')}`
      return { text: before + heading + after, cursor: lineStart + heading.length }
    },
  },
  {
    label: 'H3',
    icon: 'H3',
    action: (v, s, e) => {
      const lineStart = v.lastIndexOf('\n', s - 1) + 1
      const line = v.slice(lineStart, e) || 'Heading'
      const before = v.slice(0, lineStart)
      const after = v.slice(e)
      const heading = line.startsWith('### ') ? line.slice(4) : `### ${line.replace(/^#+\s*/, '')}`
      return { text: before + heading + after, cursor: lineStart + heading.length }
    },
  },
  {
    label: 'Unordered list',
    icon: '≡',
    action: (v, s) => {
      const lineStart = v.lastIndexOf('\n', s - 1) + 1
      const ins = '- '
      return {
        text: v.slice(0, lineStart) + ins + v.slice(lineStart),
        cursor: s + ins.length,
      }
    },
  },
  {
    label: 'Ordered list',
    icon: '1.',
    action: (v, s) => {
      const lineStart = v.lastIndexOf('\n', s - 1) + 1
      const ins = '1. '
      return {
        text: v.slice(0, lineStart) + ins + v.slice(lineStart),
        cursor: s + ins.length,
      }
    },
  },
  {
    label: 'Link',
    icon: '🔗',
    action: (v, s, e) => {
      const sel = v.slice(s, e) || 'リンクテキスト'
      const snippet = `[${sel}](url)`
      return { text: v.slice(0, s) + snippet + v.slice(e), cursor: s + sel.length + 3 }
    },
  },
  {
    label: 'Code block',
    icon: '</>',
    action: (v, s, e) => {
      const sel = v.slice(s, e) || 'code'
      const snippet = `\`\`\`\n${sel}\n\`\`\``
      return { text: v.slice(0, s) + snippet + v.slice(e), cursor: s + 4 + sel.length }
    },
  },
  {
    label: 'Blockquote',
    icon: '❝',
    action: (v, s) => {
      const lineStart = v.lastIndexOf('\n', s - 1) + 1
      const ins = '> '
      return {
        text: v.slice(0, lineStart) + ins + v.slice(lineStart),
        cursor: s + ins.length,
      }
    },
  },
]

export function MarkdownFieldInput({
  id,
  label,
  value,
  disabled,
  onChange,
}: MarkdownFieldInputProps) {
  const { t } = useTranslation()
  const [tab, setTab] = useState<'write' | 'preview'>('write')
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  const applyToolbar = useCallback(
    (action: ToolbarAction) => {
      const ta = textareaRef.current
      if (ta === null) return
      const s = ta.selectionStart
      const e = ta.selectionEnd
      const { text, cursor } = action.action(value, s, e)
      onChange(text)
      requestAnimationFrame(() => {
        ta.focus()
        ta.setSelectionRange(cursor, cursor)
      })
    },
    [value, onChange],
  )

  const tabClass = (active: boolean) =>
    [
      'pb-1.5 font-sans text-caption font-medium transition-colors focus-visible:outline-none',
      active
        ? 'border-b-2 border-accent text-text-primary'
        : 'text-text-muted hover:text-text-primary',
    ].join(' ')

  return (
    <Stack gap="xs">
      <label htmlFor={id} className="font-sans text-body font-medium text-text-primary">
        {label}
      </label>

      <div className="rounded-md border border-border shadow-sm">
        {/* ── Tab bar ── */}
        <div className="flex items-center justify-between border-b border-border px-inline-sm">
          <div className="flex gap-inline-md">
            <button
              type="button"
              onClick={() => {
                setTab('write')
              }}
              className={tabClass(tab === 'write')}
            >
              {t('admin.markdownEditor.write')}
            </button>
            <button
              type="button"
              onClick={() => {
                setTab('preview')
              }}
              className={tabClass(tab === 'preview')}
            >
              {t('admin.markdownEditor.preview')}
            </button>
          </div>
        </div>

        {/* ── Toolbar (write モードのみ) ── */}
        {tab === 'write' && (
          <div className="flex flex-wrap gap-0.5 border-b border-border bg-surface-overlay px-inline-sm py-stack-xs">
            {TOOLBAR_ACTIONS.map((action) => (
              <button
                key={action.label}
                type="button"
                title={action.label}
                disabled={disabled}
                onClick={() => {
                  applyToolbar(action)
                }}
                className="flex w-8 items-center justify-center rounded px-1.5 py-0.5 font-mono text-caption text-text-muted transition-colors hover:bg-surface-raised hover:text-text-primary disabled:cursor-not-allowed disabled:opacity-40"
              >
                {action.icon}
              </button>
            ))}
          </div>
        )}

        {/* ── Write pane ── */}
        {tab === 'write' && (
          <textarea
            ref={textareaRef}
            id={id}
            value={value}
            disabled={disabled}
            rows={16}
            onChange={(e) => {
              onChange(e.target.value)
            }}
            className="w-full rounded-b-md bg-surface-raised px-inline-md py-stack-sm font-mono text-body text-text-primary focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
          />
        )}

        {/* ── Preview pane ── */}
        {tab === 'preview' && (
          <div className="min-h-64 rounded-b-md bg-surface-raised px-inline-md py-stack-sm">
            {value.trim() === '' ? (
              <Text muted>{t('admin.markdownEditor.empty')}</Text>
            ) : (
              <PublicMarkdownContent markdown={value} />
            )}
          </div>
        )}
      </div>
    </Stack>
  )
}
