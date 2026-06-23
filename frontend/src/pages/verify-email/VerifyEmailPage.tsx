import { useEffect, useRef } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useVerifyEmailChange } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Stack, Text } from '@/shared/ui'

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
      <Card padding="lg" className="w-full max-w-sm">
        <Stack gap="lg">
          <Text as="h1" variant="heading-md">
            {t('admin.verifyEmail.pageTitle')}
          </Text>

          {token === '' || verify.isError ? (
            <div
              role="alert"
              className="rounded-md border border-danger bg-danger-weak px-inline-sm py-stack-xs text-sm text-danger"
            >
              {token === '' ? t('admin.verifyEmail.invalid') : errorMessage()}
            </div>
          ) : verify.isSuccess ? (
            <Stack gap="md">
              <div
                role="status"
                className="rounded-md border border-success bg-success-weak px-inline-sm py-stack-xs text-sm text-success"
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
      </Card>
    </div>
  )
}
