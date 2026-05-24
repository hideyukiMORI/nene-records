export type TextVariant = 'body' | 'caption' | 'heading-sm' | 'heading-md'
export type TextElement = 'p' | 'span' | 'h1' | 'h2' | 'h3'

export interface TextProps {
  as?: TextElement
  variant?: TextVariant
  muted?: boolean
  children: React.ReactNode
  className?: string
}

const variantClasses: Record<TextVariant, string> = {
  body: 'text-body leading-body font-normal',
  caption: 'text-caption leading-body font-normal',
  'heading-sm': 'text-heading-sm leading-heading font-semibold',
  'heading-md': 'text-heading-md leading-heading font-semibold',
}

export function Text({
  as: Component = 'p',
  variant = 'body',
  muted = false,
  children,
  className,
}: TextProps) {
  const classes = [
    'font-sans',
    variantClasses[variant],
    muted ? 'text-text-muted' : 'text-text-primary',
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return <Component className={classes}>{children}</Component>
}
