import { useCallback } from 'react'
import { useForm } from 'react-hook-form'
import type { User } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import type { AdminResetPasswordFormValues } from '../hooks/use-manage-users-page'

export interface AdminResetPasswordFormProps {
  user: User
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: AdminResetPasswordFormValues) => Promise<void>
  onCancel: () => void
}

export function AdminResetPasswordForm({
  user,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: AdminResetPasswordFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<AdminResetPasswordFormValues>({
    defaultValues: { newPassword: '' },
  })

  const submit = useCallback(
    async (values: AdminResetPasswordFormValues) => {
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
          {t('admin.users.resetPassword.title')}
        </Text>
        <Text muted>{t('admin.users.resetPassword.description', { email: user.email })}</Text>
        <Input
          id="admin-reset-new-password"
          label={t('admin.users.resetPassword.newPasswordLabel')}
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
              ? t('admin.users.resetPassword.resetting')
              : t('admin.users.resetPassword.submit')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('admin.users.cancel')}
          </Button>
        </div>
      </Stack>
    </form>
  )
}
