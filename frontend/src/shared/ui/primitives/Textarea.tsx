import { forwardRef } from 'react'

export interface TextareaProps {
  id: string
  label?: string
  name?: string
  value?: string
  onChange: (event: React.ChangeEvent<HTMLTextAreaElement>) => void
  onBlur?: (event: React.FocusEvent<HTMLTextAreaElement>) => void
  disabled?: boolean
  error?: string | undefined
  placeholder?: string
  rows?: number
  /** Render the value in the mono face (Markdown / code bodies). */
  mono?: boolean
  className?: string
  'aria-label'?: string
}

/**
 * Textarea — multi-line counterpart to Input, sharing its border, fill and
 * accent focus ring so every form field reads the same.
 */
export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
  {
    id,
    label,
    name,
    value,
    onChange,
    onBlur,
    disabled = false,
    error,
    placeholder,
    rows = 4,
    mono = false,
    className,
    'aria-label': ariaLabel,
  },
  ref,
) {
  const errorId = error !== undefined ? `${id}-error` : undefined

  const textarea = (
    <textarea
      ref={ref}
      id={id}
      name={name}
      value={value}
      onChange={onChange}
      onBlur={onBlur}
      disabled={disabled}
      rows={rows}
      placeholder={placeholder}
      aria-label={ariaLabel}
      aria-invalid={error !== undefined}
      aria-describedby={errorId}
      className={[
        'rounded-sm border border-border bg-surface-raised px-inline-md py-stack-sm text-text-primary shadow-sm',
        mono ? 'font-mono text-caption' : 'font-sans text-body',
        'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50',
        error !== undefined ? 'border-danger' : '',
        className,
      ]
        .filter(Boolean)
        .join(' ')}
    />
  )

  if (label === undefined) {
    return textarea
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <label htmlFor={id} className="font-sans text-body font-medium text-text-primary">
        {label}
      </label>
      {textarea}
      {error !== undefined ? (
        <span id={errorId} className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
    </div>
  )
})
