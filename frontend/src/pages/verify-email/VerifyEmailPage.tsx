import { useEffect, useRef } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useVerifyEmailChange } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

/**
 * Landing page for the verification link emailed to a user's new address (#283).
 * Reads `?token=` and confirms the pending email change on mount.
 */
export function VerifyEmailPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') ?? ''

  const verify = useVerifyEmailChange()
  // Guard against double-firing under React 18 StrictMode's dev double-invoke.
  const startedRef = useRef(false)

  useEffect(() => {
    if (startedRef.current || token === '') {
      return
    }
    startedRef.current = true
    verify.mutate(token)
  }, [token, verify])

  const errorMessage = (): string => {
    const status = verify.error?.status
    if (status === 410) {
      return t('admin.verifyEmail.expired')
    }
    if (status === 409) {
      return t('admin.verifyEmail.conflict')
    }
    return t('admin.verifyEmail.invalid')
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-inline-md">
      <div className="w-full max-w-sm rounded-lg border border-border bg-surface-raised p-stack-lg shadow-sm">
        <Stack gap="lg">
          <Text as="h1" variant="heading-md">
            {t('admin.verifyEmail.pageTitle')}
          </Text>

          {token === '' || verify.isError ? (
            <div
              role="alert"
              className="rounded-md border border-red-200 bg-red-50 px-inline-sm py-stack-xs text-sm text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300"
            >
              {token === '' ? t('admin.verifyEmail.invalid') : errorMessage()}
            </div>
          ) : verify.isSuccess ? (
            <Stack gap="md">
              <div
                role="status"
                className="rounded-md border border-green-200 bg-green-50 px-inline-sm py-stack-xs text-sm text-green-700 dark:border-green-800 dark:bg-green-950/30 dark:text-green-300"
              >
                {t('admin.verifyEmail.success')}
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
            <Text muted>{t('admin.verifyEmail.verifying')}</Text>
          )}
        </Stack>
      </div>
    </div>
  )
}
