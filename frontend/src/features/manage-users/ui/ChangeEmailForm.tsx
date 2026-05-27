import { useCallback } from 'react'
import { useForm } from 'react-hook-form'
import type { User } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import type { ChangeEmailFormValues } from '../hooks/use-manage-users-page'

export interface ChangeEmailFormProps {
  user: User
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: ChangeEmailFormValues) => Promise<void>
  onCancel: () => void
}

export function ChangeEmailForm({
  user,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: ChangeEmailFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<ChangeEmailFormValues>({
    defaultValues: { email: user.email },
  })

  const submit = useCallback(
    async (values: ChangeEmailFormValues) => {
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
          {t('admin.users.changeEmail.title')}
        </Text>
        <Text muted>{t('admin.users.changeEmail.description', { email: user.email })}</Text>
        <Input
          id="change-email-address"
          label={t('admin.users.changeEmail.emailLabel')}
          type="email"
          error={errors.email?.message}
          autoComplete="email"
          disabled={isSubmitting}
          {...register('email', {
            required: t('admin.users.validation.emailRequired'),
            pattern: {
              value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
              message: t('admin.users.validation.emailInvalid'),
            },
          })}
        />
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <div className="flex gap-2">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? t('admin.users.changeEmail.saving')
              : t('admin.users.changeEmail.submit')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('admin.users.cancel')}
          </Button>
        </div>
      </Stack>
    </form>
  )
}
