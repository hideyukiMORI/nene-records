import { forwardRef } from 'react'

export interface InputProps {
  id: string
  label: string
  type?: 'text' | 'email' | 'password' | 'number' | 'datetime-local'
  name?: string
  value?: string
  onChange: (event: React.ChangeEvent<HTMLInputElement>) => void
  onBlur?: (event: React.FocusEvent<HTMLInputElement>) => void
  disabled?: boolean
  error?: string | undefined
  autoComplete?: string
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  {
    id,
    label,
    type = 'text',
    name,
    value,
    onChange,
    onBlur,
    disabled = false,
    error,
    autoComplete,
  },
  ref,
) {
  const errorId = error !== undefined ? `${id}-error` : undefined

  return (
    <div className="flex flex-col gap-stack-xs">
      <label htmlFor={id} className="font-sans text-body font-medium text-text-primary">
        {label}
      </label>
      <input
        ref={ref}
        id={id}
        type={type}
        name={name}
        value={value}
        onChange={onChange}
        onBlur={onBlur}
        disabled={disabled}
        autoComplete={autoComplete}
        aria-invalid={error !== undefined}
        aria-describedby={errorId}
        className={[
          'rounded-sm border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm',
          'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50',
          error !== undefined ? 'border-danger' : '',
        ]
          .filter(Boolean)
          .join(' ')}
      />
      {error !== undefined ? (
        <span id={errorId} className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
    </div>
  )
})
