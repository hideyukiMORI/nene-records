import type { ReactNode } from 'react'
import { Button } from '../primitives/Button'
import { Stack } from '../primitives/Stack'
import { Text } from '../primitives/Text'

export interface LoadingStateProps {
  /** Muted status line, already localized. */
  children: ReactNode
}

/** LoadingState — muted status line shown while a query is in flight. */
export function LoadingState({ children }: LoadingStateProps) {
  return <Text muted>{children}</Text>
}

export interface ErrorStateProps {
  /** Optional bold heading above the message. */
  title?: string
  /** Muted error message, already localized. */
  message: ReactNode
  /** When provided, renders a retry button. */
  onRetry?: () => void
  /** Retry button label (already localized). */
  retryLabel?: string
}

/**
 * ErrorState — standard "could not load" block: optional heading, a muted
 * message and an optional retry button. Replaces the per-panel error markup.
 */
export function ErrorState({ title, message, onRetry, retryLabel }: ErrorStateProps) {
  return (
    <Stack gap="sm">
      {title !== undefined ? <Text variant="heading-sm">{title}</Text> : null}
      <Text muted>{message}</Text>
      {onRetry !== undefined ? (
        <div>
          <Button variant="secondary" size="sm" onClick={onRetry}>
            {retryLabel}
          </Button>
        </div>
      ) : null}
    </Stack>
  )
}
