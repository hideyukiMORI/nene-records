import { type ReactNode } from 'react'

interface RepeaterIconButtonProps {
  title: string
  disabled: boolean
  onClick: () => void
  children: ReactNode
  /** Use the danger hover colour (for destructive actions like delete). */
  danger?: boolean
}

/**
 * Small icon-only control (move up / move down / delete) used by the block
 * board and the inspector repeaters. Replaces the hand-rolled `<button>` markup
 * those lists duplicated.
 */
export function RepeaterIconButton({
  title,
  disabled,
  onClick,
  children,
  danger = false,
}: RepeaterIconButtonProps) {
  return (
    <button
      type="button"
      className={`rounded p-1 text-text-muted disabled:opacity-40 ${
        danger ? 'hover:text-danger' : 'hover:text-text-primary'
      }`}
      title={title}
      disabled={disabled}
      onClick={onClick}
    >
      {children}
    </button>
  )
}
