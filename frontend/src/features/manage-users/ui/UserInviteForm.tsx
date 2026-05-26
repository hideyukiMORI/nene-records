import { useCallback } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import type { InviteFormValues } from '../hooks/use-manage-users-page'

export interface UserInviteFormProps {
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: InviteFormValues) => Promise<void>
  onCancel: () => void
}

export function UserInviteForm({
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: UserInviteFormProps) {
  const { t } = useTranslation()
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<InviteFormValues>({
    defaultValues: { email: '', role: 'editor' },
  })

  const submit = useCallback(
    async (values: InviteFormValues) => {
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
          {t('admin.users.invite.title')}
        </Text>
        <Input
          id="invite-email"
          label={t('admin.users.invite.emailLabel')}
          type="email"
          placeholder="editor@example.com"
          error={errors.email?.message}
          autoComplete="off"
          disabled={isSubmitting}
          {...register('email', { required: t('admin.users.validation.emailRequired') })}
        />
        <div>
          <label htmlFor="invite-role" className="mb-1 block text-sm font-medium text-text-primary">
            {t('admin.users.invite.roleLabel')}
          </label>
          <select
            id="invite-role"
            disabled={isSubmitting}
            className="w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-accent disabled:cursor-not-allowed disabled:opacity-50"
            {...register('role')}
          >
            <option value="editor">{t('admin.users.role.editor')}</option>
            <option value="admin">{t('admin.users.role.admin')}</option>
          </select>
        </div>
        {serverErrorTitle !== null ? (
          <Text muted className="text-red-500">
            {serverErrorTitle}
          </Text>
        ) : null}
        <div className="flex gap-2">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? t('admin.users.invite.inviting') : t('admin.users.invite.submit')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('admin.users.cancel')}
          </Button>
        </div>
      </Stack>
    </form>
  )
}
