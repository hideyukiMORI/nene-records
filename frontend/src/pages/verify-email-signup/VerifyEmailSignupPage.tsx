import { useEffect, useRef } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
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
  const navigate = useNavigate()
  const { t } = useTranslation()
  const fired = useRef(false)

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
          {confirm.isSuccess && (
            <Button
              type="button"
              className="w-full"
              onClick={() => {
                void navigate('/admin')
              }}
            >
              {t('admin.verifySignup.goToAdmin')}
            </Button>
          )}
        </Stack>
      </Card>
    </div>
  )
}
