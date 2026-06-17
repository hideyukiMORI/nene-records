import type { HTMLAttributes, ReactNode } from 'react'

export type CardPadding = 'none' | 'row' | 'md' | 'lg'
export type CardElement = 'div' | 'li' | 'section' | 'article' | 'form'

export interface CardProps extends HTMLAttributes<HTMLElement> {
  as?: CardElement
  /** `row` for list items, `md` (default) for cards, `lg` for section panels. */
  padding?: CardPadding
  /** Adds the accent hover treatment for clickable cards. */
  interactive?: boolean
  className?: string
  children: ReactNode
}

const paddingClasses: Record<CardPadding, string> = {
  none: '',
  row: 'px-inline-md py-stack-sm',
  md: 'p-inline-md',
  lg: 'p-stack-lg',
}

/**
 * Card — the single raised surface used across the admin: a bordered,
 * rounded, shadowed `--surface-raised` box. List rows, stat tiles, form
 * panels and section blocks all share this so borders/radius/elevation
 * are defined once. Choose `padding` per density; pass `className` for layout.
 *
 * Extends the root element's HTML attributes, so callers can attach interaction
 * props (onClick, draggable, onDragStart/End, data-*, etc.); they are forwarded
 * to the chosen element. This is what makes a Card row clickable / draggable
 * (e.g. the layout builder's widget cards).
 */
export function Card({
  as: Component = 'div',
  padding = 'md',
  interactive = false,
  className,
  children,
  ...rest
}: CardProps) {
  const classes = [
    'rounded-md border border-border bg-surface-raised shadow-sm',
    paddingClasses[padding],
    interactive ? 'transition-colors hover:border-accent hover:bg-surface' : '',
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return (
    <Component className={classes} {...rest}>
      {children}
    </Component>
  )
}
