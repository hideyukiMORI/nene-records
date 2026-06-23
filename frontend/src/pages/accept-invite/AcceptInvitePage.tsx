import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useAcceptInvite } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Input, Stack, Text } from '@/shared/ui'

interface FormValues {
  password: string
}

export function AcceptInvitePage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') ?? ''

  const [success, setSuccess] = useState(false)
  const acceptInvite = useAcceptInvite()

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    defaultValues: { password: '' },
  })

  const onSubmit = async (values: FormValues) => {
    await acceptInvite.mutateAsync({ token, password: values.password })
    setSuccess(true)
  }

  if (!token) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-surface px-inline-md">
        <Card padding="lg" className="w-full max-w-sm">
          <Text muted>{t('admin.acceptInvite.invalidToken')}</Text>
        </Card>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-inline-md">
      <Card padding="lg" className="w-full max-w-sm">
        <Stack gap="lg">
          <Stack gap="xs">
            <Text as="h1" variant="heading-md">
              {t('admin.acceptInvite.pageTitle')}
            </Text>
            <Text muted>{t('admin.acceptInvite.description')}</Text>
          </Stack>

          {success ? (
            <Stack gap="md">
              <div
                role="status"
                className="rounded-md border border-success bg-success-weak px-inline-sm py-stack-xs text-sm text-success"
              >
                {t('admin.acceptInvite.success')}
              </div>
              <Button
                onClick={() => {
                  void navigate('/login')
                }}
              >
                {t('admin.auth.signIn')}
              </Button>
            </Stack>
          ) : (
            <form
              onSubmit={(e) => {
                void handleSubmit(onSubmit)(e)
              }}
            >
              <Stack gap="md">
                {acceptInvite.isError ? (
                  <div
                    role="alert"
                    className="rounded-md border border-danger bg-danger-weak px-inline-sm py-stack-xs text-sm text-danger"
                  >
                    {t('admin.acceptInvite.invalidToken')}
                  </div>
                ) : null}
                <Input
                  id="accept-invite-password"
                  label={t('admin.acceptInvite.passwordLabel')}
                  type="password"
                  error={errors.password?.message}
                  autoComplete="new-password"
                  disabled={isSubmitting}
                  {...register('password', {
                    required: t('admin.users.validation.passwordRequired'),
                    minLength: {
                      value: 8,
                      message: t('admin.users.validation.passwordMinLength'),
                    },
                  })}
                />
                <Button type="submit" disabled={isSubmitting} className="w-full">
                  {isSubmitting
                    ? t('admin.acceptInvite.submitting')
                    : t('admin.acceptInvite.submit')}
                </Button>
              </Stack>
            </form>
          )}
        </Stack>
      </Card>
    </div>
  )
}
