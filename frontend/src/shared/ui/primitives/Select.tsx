import { forwardRef } from 'react'

export type SelectSize = 'sm' | 'md'

export interface SelectProps {
  id?: string
  /** Visible label; omit and pass `aria-label` for compact inline selects. */
  label?: string
  name?: string
  value?: string | undefined
  onChange: (event: React.ChangeEvent<HTMLSelectElement>) => void
  onBlur?: (event: React.FocusEvent<HTMLSelectElement>) => void
  disabled?: boolean
  size?: SelectSize
  error?: string | undefined
  'aria-label'?: string
  className?: string
  /** `<option>` elements. */
  children: React.ReactNode
}

const sizeClasses: Record<SelectSize, string> = {
  sm: 'px-inline-sm py-stack-xs text-caption',
  md: 'px-inline-md py-stack-sm text-body',
}

/**
 * Select — themed `<select>` matching the Input primitive (rounded-sm border,
 * surface-raised fill, accent focus ring). Pass `<option>`s as children.
 */
export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
  {
    id,
    label,
    name,
    value,
    onChange,
    onBlur,
    disabled = false,
    size = 'md',
    error,
    className,
    children,
    'aria-label': ariaLabel,
  },
  ref,
) {
  const errorId = error !== undefined && id !== undefined ? `${id}-error` : undefined

  const select = (
    <select
      ref={ref}
      id={id}
      name={name}
      value={value}
      onChange={onChange}
      onBlur={onBlur}
      disabled={disabled}
      aria-label={ariaLabel}
      aria-invalid={error !== undefined}
      aria-describedby={errorId}
      className={[
        'rounded-sm border border-border bg-surface-raised font-sans text-text-primary shadow-sm',
        sizeClasses[size],
        'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50',
        error !== undefined ? 'border-danger' : '',
        className,
      ]
        .filter(Boolean)
        .join(' ')}
    >
      {children}
    </select>
  )

  if (label === undefined) {
    return select
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <label htmlFor={id} className="font-sans text-body font-medium text-text-primary">
        {label}
      </label>
      {select}
      {error !== undefined ? (
        <span id={errorId} className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
    </div>
  )
})
