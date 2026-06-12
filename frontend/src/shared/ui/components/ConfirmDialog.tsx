import type { ReactNode } from 'react'
import { Button } from '@/shared/ui/primitives/Button'
import { Stack } from '@/shared/ui/primitives/Stack'
import { Text } from '@/shared/ui/primitives/Text'

export interface ConfirmDialogProps {
  open: boolean
  title: string
  description?: string
  errorDetail?: string | null
  confirmLabel?: string
  cancelLabel?: string
  isPending?: boolean
  confirmDisabled?: boolean
  children?: ReactNode
  onConfirm: () => void
  onCancel: () => void
}

/**
 * ConfirmDialog — destructive or important action confirmation.
 *
 * In:  open, title, description, confirmLabel, cancelLabel, isPending,
 *      confirmDisabled, children (extra content under the description)
 * Out: onConfirm(), onCancel()
 *
 * Does not: perform mutations or know entity ids.
 */
export function ConfirmDialog({
  open,
  title,
  description,
  errorDetail = null,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  isPending = false,
  confirmDisabled = false,
  children,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  if (!open) {
    return null
  }

  return (
    <div className="fixed inset-0 z-modal flex items-center justify-center px-inline-md py-stack-md">
      <button
        type="button"
        aria-label="Close dialog"
        className="absolute inset-0 bg-surface-overlay/80"
        onClick={onCancel}
      />
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-dialog-title"
        className="relative w-full max-w-md rounded-md border border-border bg-surface-raised p-inline-lg shadow-md"
      >
        <Stack gap="md">
          <Stack gap="xs">
            <Text as="h2" id="confirm-dialog-title" variant="heading-sm">
              {title}
            </Text>
            {description !== undefined ? <Text muted>{description}</Text> : null}
          </Stack>
          {children}
          {errorDetail !== null ? (
            <div
              role="alert"
              className="rounded-md border border-red-200 bg-red-50 px-inline-sm py-stack-xs text-sm text-red-700"
            >
              {errorDetail}
            </div>
          ) : null}
          <Stack direction="horizontal" gap="sm">
            <Button variant="secondary" disabled={isPending} onClick={onCancel}>
              {cancelLabel}
            </Button>
            <Button variant="danger" disabled={isPending || confirmDisabled} onClick={onConfirm}>
              {confirmLabel}
            </Button>
          </Stack>
        </Stack>
      </div>
    </div>
  )
}
