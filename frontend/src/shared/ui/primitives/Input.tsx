import { forwardRef } from 'react'

export interface InputProps extends Omit<
  React.ComponentPropsWithoutRef<'input'>,
  'type' | 'value' | 'onChange'
> {
  id: string
  /** Visible field label. Omit only when an external <label htmlFor={id}> is rendered. */
  label?: string
  type?: 'text' | 'email' | 'password' | 'number' | 'datetime-local'
  value?: string
  onChange: (event: React.ChangeEvent<HTMLInputElement>) => void
  error?: string | undefined
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { id, label, type = 'text', value, onChange, error, className, ...rest },
  ref,
) {
  const errorId = error !== undefined ? `${id}-error` : undefined

  return (
    <div className="flex flex-col gap-stack-xs">
      {label !== undefined ? (
        <label htmlFor={id} className="font-sans text-body font-medium text-text-primary">
          {label}
        </label>
      ) : null}
      <input
        ref={ref}
        id={id}
        type={type}
        value={value}
        onChange={onChange}
        aria-invalid={error !== undefined}
        aria-describedby={errorId}
        className={[
          'rounded-sm border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm',
          'focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50',
          error !== undefined ? 'border-danger' : '',
          className ?? '',
        ]
          .filter(Boolean)
          .join(' ')}
        {...rest}
      />
      {error !== undefined ? (
        <span id={errorId} className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
    </div>
  )
})
