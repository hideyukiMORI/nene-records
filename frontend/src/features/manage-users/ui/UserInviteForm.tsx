import { useCallback } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Input, Select, Stack, Text } from '@/shared/ui'
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
    <Card
      as="form"
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
        <Select
          id="invite-role"
          label={t('admin.users.invite.roleLabel')}
          disabled={isSubmitting}
          className="w-full"
          {...register('role')}
        >
          <option value="editor">{t('admin.users.role.editor')}</option>
          <option value="admin">{t('admin.users.role.admin')}</option>
        </Select>
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
    </Card>
  )
}
