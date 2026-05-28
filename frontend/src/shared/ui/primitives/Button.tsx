export type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost'
export type ButtonSize = 'sm' | 'md'

export interface ButtonProps {
  variant?: ButtonVariant
  size?: ButtonSize
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  children: React.ReactNode
  className?: string
  onClick?: (event: React.MouseEvent<HTMLButtonElement>) => void
  onFocus?: (event: React.FocusEvent<HTMLButtonElement>) => void
  onBlur?: (event: React.FocusEvent<HTMLButtonElement>) => void
  /** Stable hook for E2E tests; prefer this over matching on (i18n) button text. */
  'data-testid'?: string
}

const variantClasses: Record<ButtonVariant, string> = {
  primary:
    'bg-accent text-text-inverse hover:bg-accent-hover border-transparent focus-visible:shadow-focus',
  secondary:
    'bg-surface-raised text-text-primary hover:bg-surface-overlay border-border focus-visible:shadow-focus',
  danger:
    'bg-danger text-text-inverse hover:bg-danger-hover border-transparent focus-visible:shadow-focus',
  ghost:
    'bg-transparent text-text-muted hover:text-text-primary border-transparent focus-visible:shadow-focus',
}

const sizeClasses: Record<ButtonSize, string> = {
  sm: 'px-inline-sm py-stack-xs text-caption',
  md: 'px-inline-md py-stack-sm text-body',
}

export function Button({
  variant = 'primary',
  size = 'md',
  disabled = false,
  type = 'button',
  children,
  className,
  onClick,
  onFocus,
  onBlur,
  'data-testid': dataTestId,
}: ButtonProps) {
  const classes = [
    'inline-flex items-center justify-center rounded-md border font-sans font-medium transition-colors duration-fast ease-default',
    'focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
    variantClasses[variant],
    sizeClasses[size],
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return (
    <button
      type={type}
      disabled={disabled}
      className={classes}
      onClick={onClick}
      onFocus={onFocus}
      onBlur={onBlur}
      data-testid={dataTestId}
    >
      {children}
    </button>
  )
}
