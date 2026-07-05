import { useId, type ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Button } from '@/shared/ui/primitives/Button'
import { Stack } from '@/shared/ui/primitives/Stack'
import { Text } from '@/shared/ui/primitives/Text'
import { Modal } from './Modal'

export interface ConfirmDialogProps {
  open: boolean
  title: string
  description?: string | undefined
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
  confirmLabel,
  cancelLabel,
  isPending = false,
  confirmDisabled = false,
  children,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  const { t } = useTranslation()
  // Unique per instance so simultaneously-open dialogs don't share an
  // aria-labelledby target (screen-reader label resolution would break).
  const titleId = useId()

  if (!open) {
    return null
  }

  return (
    <Modal
      onClose={onCancel}
      closeLabel={t('common.dialog.close')}
      labelledBy={titleId}
      panelClassName="max-w-md shadow-md"
    >
      <Stack gap="md">
        <Stack gap="xs">
          <Text as="h2" id={titleId} variant="heading-sm">
            {title}
          </Text>
          {description !== undefined ? <Text muted>{description}</Text> : null}
        </Stack>
        {children}
        {errorDetail !== null ? (
          <div
            role="alert"
            className="rounded-md border border-danger bg-danger-weak px-inline-sm py-stack-xs text-sm text-danger"
          >
            {errorDetail}
          </div>
        ) : null}
        <Stack direction="horizontal" gap="sm">
          <Button variant="secondary" disabled={isPending} onClick={onCancel}>
            {cancelLabel ?? t('common.actions.cancel')}
          </Button>
          <Button variant="danger" disabled={isPending || confirmDisabled} onClick={onConfirm}>
            {confirmLabel ?? t('common.actions.confirm')}
          </Button>
        </Stack>
      </Stack>
    </Modal>
  )
}
