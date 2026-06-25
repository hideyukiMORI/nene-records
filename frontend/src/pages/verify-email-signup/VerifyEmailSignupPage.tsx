import { useEffect, useRef } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useConfirmEmail } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, NeneMark, Stack, Text } from '@/shared/ui'

/**
 * Public landing for the signup email-verification link (`/verify-email?token=…`).
 * Confirms the token on mount; token-based, so it works before the new admin has
 * signed in on their tenant subdomain.
 */
export function VerifyEmailSignupPage() {
  const [params] = useSearchParams()
  const token = params.get('token') ?? ''
  const confirm = useConfirmEmail()
  const { t } = useTranslation()
  const fired = useRef(false)

  // Verification is served from the apex; send the admin to their own subdomain
  // (slug.<this-host>) to sign in, since the session cookie is host-only per tenant.
  const slug = confirm.data?.slug ?? null
  const tenantLoginUrl =
    slug !== null && typeof window !== 'undefined'
      ? `https://${slug}.${window.location.hostname}/login`
      : null

  useEffect(() => {
    if (fired.current || token === '') return
    fired.current = true
    confirm.mutate(token)
  }, [token, confirm])

  const body = (() => {
    if (token === '') return t('admin.verifySignup.missing')
    if (confirm.isPending) return t('admin.verifySignup.pending')
    if (confirm.isSuccess) return t('admin.verifySignup.success')
    if (confirm.isError) return t('admin.verifySignup.failed')
    return t('admin.verifySignup.pending')
  })()

  return (
    <div className="relative flex min-h-screen items-center justify-center bg-surface px-inline-md">
      <Card padding="lg" className="w-full max-w-sm">
        <Stack gap="lg">
          <div className="flex items-center gap-inline-sm">
            <NeneMark size={26} className="text-accent" />
            <span className="font-chrome text-base font-bold tracking-tight text-text-primary">
              NeNe Records
            </span>
          </div>
          <Stack gap="xs">
            <Text as="h1" variant="heading-md">
              {t('admin.verifySignup.title')}
            </Text>
            <Text muted>{body}</Text>
          </Stack>
          {confirm.isSuccess && tenantLoginUrl !== null && (
            <Button
              type="button"
              className="w-full"
              onClick={() => {
                window.location.href = tenantLoginUrl
              }}
            >
              {t('admin.verifySignup.goToLogin')}
            </Button>
          )}
        </Stack>
      </Card>
    </div>
  )
}
