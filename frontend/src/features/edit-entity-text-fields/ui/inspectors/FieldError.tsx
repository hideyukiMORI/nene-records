import { type ReactNode } from 'react'

interface FieldErrorProps {
  children: ReactNode
}

/** Inline `role="alert"` validation message used across the block inspector fields. */
export function FieldError({ children }: FieldErrorProps) {
  return (
    <span role="alert" className="font-sans text-caption text-danger">
      {children}
    </span>
  )
}
