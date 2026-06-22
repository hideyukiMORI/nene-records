export type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost' | 'subtle'
export type ButtonSize = 'sm' | 'md'

export interface ButtonProps extends Omit<React.ComponentPropsWithoutRef<'button'>, 'type'> {
  variant?: ButtonVariant
  size?: ButtonSize
  type?: 'button' | 'submit' | 'reset'
  children: React.ReactNode
  /** Stable hook for E2E tests; prefer this over matching on (i18n) button text. */
  'data-testid'?: string
}

// Variants follow the redesign spec (§05) — the only canonical set is
// primary · ghost · subtle · danger:
//   primary — filled accent, darkens on hover
//   ghost   — bordered + transparent (the page shows through); the "Cancel" button
//   subtle  — overlay fill, no border; the "Export" button
//   danger  — red outline that fills on hover (spec defines `--danger` but no resting fill)
// `secondary` is not a spec variant; it is kept as a legacy alias of `ghost`
// so existing call sites stay spec-compliant without a mass rename.
const GHOST =
  'bg-transparent text-text-primary hover:bg-surface-overlay border-border focus-visible:shadow-focus'

const variantClasses: Record<ButtonVariant, string> = {
  primary:
    'bg-accent text-text-inverse hover:bg-accent-hover border-transparent focus-visible:shadow-focus',
  ghost: GHOST,
  secondary: GHOST,
  subtle:
    'bg-surface-overlay text-text-primary hover:bg-surface-raised border-transparent focus-visible:shadow-focus',
  danger:
    'bg-transparent text-danger hover:bg-danger hover:text-text-inverse border-danger focus-visible:shadow-focus',
}

const sizeClasses: Record<ButtonSize, string> = {
  sm: 'px-inline-sm py-stack-xs text-caption',
  md: 'px-inline-md py-stack-sm text-body',
}

export function Button({
  variant = 'primary',
  size = 'md',
  type = 'button',
  children,
  className,
  ...rest
}: ButtonProps) {
  const classes = [
    'inline-flex items-center justify-center rounded-sm border font-chrome font-semibold tracking-tight transition-colors duration-fast ease-default',
    'focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
    variantClasses[variant],
    sizeClasses[size],
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return (
    <button type={type} className={classes} {...rest}>
      {children}
    </button>
  )
}
