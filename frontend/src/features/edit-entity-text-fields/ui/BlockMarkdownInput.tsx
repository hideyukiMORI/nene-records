import { MarkdownEditorShell } from './MarkdownEditorShell'

interface BlockMarkdownInputProps {
  id: string
  label: string
  value: string
  disabled: boolean
  error?: string | undefined
  onChange: (value: string) => void
}

/**
 * Compact Markdown editor for the block inspector: textarea + write/preview tabs.
 * Shares the tab/preview chrome with {@link MarkdownEditorShell}; no toolbar.
 */
export function BlockMarkdownInput({
  id,
  label,
  value,
  disabled,
  error,
  onChange,
}: BlockMarkdownInputProps) {
  return (
    <MarkdownEditorShell id={id} label={label} value={value} error={error} size="compact">
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
    </MarkdownEditorShell>
  )
}
