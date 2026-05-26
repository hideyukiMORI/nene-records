import { useCallback } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import type { ChangeOwnPasswordFormValues } from '../hooks/use-manage-users-page'

export interface ChangeOwnPasswordFormProps {
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: ChangeOwnPasswordFormValues) => Promise<void>
  onCancel: () => void
}

export function ChangeOwnPasswordForm({
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: ChangeOwnPasswordFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<ChangeOwnPasswordFormValues>({
    defaultValues: { currentPassword: '', newPassword: '' },
  })

  const submit = useCallback(
    async (values: ChangeOwnPasswordFormValues) => {
      await onSubmit(values)
      reset()
    },
    [onSubmit, reset],
  )

  return (
    <form
      className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
      onSubmit={(event) => {
        void handleSubmit(submit)(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.users.changeOwnPassword.title')}
        </Text>
        <Input
          id="own-current-password"
          label={t('admin.users.changeOwnPassword.currentPasswordLabel')}
          type="password"
          error={errors.currentPassword?.message}
          autoComplete="current-password"
          disabled={isSubmitting}
          {...register('currentPassword', {
            required: t('admin.users.validation.passwordRequired'),
          })}
        />
        <Input
          id="own-new-password"
          label={t('admin.users.changeOwnPassword.newPasswordLabel')}
          type="password"
          error={errors.newPassword?.message}
          autoComplete="new-password"
          disabled={isSubmitting}
          {...register('newPassword', {
            required: t('admin.users.validation.passwordRequired'),
            minLength: {
              value: 8,
              message: t('admin.users.validation.passwordMinLength'),
            },
          })}
        />
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <div className="flex gap-2">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? t('admin.users.changeOwnPassword.saving')
              : t('admin.users.changeOwnPassword.submit')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('admin.users.cancel')}
          </Button>
        </div>
      </Stack>
    </form>
  )
}
