import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useConfirmPasswordReset } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

interface FormValues {
  newPassword: string
}

export function ResetPasswordPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') ?? ''

  const [success, setSuccess] = useState(false)
  const confirmReset = useConfirmPasswordReset()

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    defaultValues: { newPassword: '' },
  })

  const onSubmit = async (values: FormValues) => {
    await confirmReset.mutateAsync({ token, newPassword: values.newPassword })
    setSuccess(true)
  }

  if (!token) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-surface px-inline-md">
        <div className="w-full max-w-sm rounded-lg border border-border bg-surface-raised p-stack-lg shadow-sm">
          <Text muted>{t('admin.resetPassword.invalidToken')}</Text>
        </div>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-inline-md">
      <div className="w-full max-w-sm rounded-lg border border-border bg-surface-raised p-stack-lg shadow-sm">
        <Stack gap="lg">
          <Stack gap="xs">
            <Text as="h1" variant="heading-md">
              {t('admin.resetPassword.pageTitle')}
            </Text>
            <Text muted>{t('admin.resetPassword.description')}</Text>
          </Stack>

          {success ? (
            <Stack gap="md">
              <div
                role="status"
                className="rounded-md border border-green-200 bg-green-50 px-inline-sm py-stack-xs text-sm text-green-700 dark:border-green-800 dark:bg-green-950/30 dark:text-green-300"
              >
                {t('admin.resetPassword.success')}
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
                {confirmReset.isError ? (
                  <div
                    role="alert"
                    className="rounded-md border border-red-200 bg-red-50 px-inline-sm py-stack-xs text-sm text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300"
                  >
                    {t('admin.resetPassword.invalidToken')}
                  </div>
                ) : null}
                <Input
                  id="reset-password-new"
                  label={t('admin.resetPassword.newPasswordLabel')}
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
                <Button type="submit" disabled={isSubmitting} className="w-full">
                  {isSubmitting
                    ? t('admin.resetPassword.submitting')
                    : t('admin.resetPassword.submit')}
                </Button>
              </Stack>
            </form>
          )}
        </Stack>
      </div>
    </div>
  )
}
