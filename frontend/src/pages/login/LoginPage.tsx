import { useState, type SyntheticEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { useLogin } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Input, NeneMark, Stack, Text } from '@/shared/ui'

export function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const navigate = useNavigate()
  const login = useLogin()
  const { t } = useTranslation()

  const handleSubmit = (e: SyntheticEvent) => {
    e.preventDefault()
    login.mutate(
      { email, password },
      {
        onSuccess: () => {
          void navigate('/admin')
        },
      },
    )
  }

  return (
    <div className="relative flex min-h-screen items-center justify-center bg-surface px-inline-md">
      <Card padding="lg" className="w-full max-w-sm">
        <Stack gap="lg">
          <Stack gap="md">
            <div className="flex items-center gap-inline-sm">
              <NeneMark size={26} className="text-accent" />
              <span className="font-chrome text-base font-bold tracking-tight text-text-primary">
                NeNe Records
              </span>
            </div>
            <Stack gap="xs">
              <Text as="h1" variant="heading-md">
                {t('admin.auth.appTitle')}
              </Text>
              <Text muted>{t('admin.auth.subtitle')}</Text>
            </Stack>
          </Stack>

          {login.isError && (
            <div
              role="alert"
              className="rounded-sm border border-danger bg-danger/10 px-inline-sm py-stack-xs text-caption font-medium text-danger"
            >
              {t('admin.auth.invalidCredentials')}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <Stack gap="md">
              <Input
                id="email"
                label={t('admin.auth.emailLabel')}
                type="email"
                value={email}
                onChange={(e) => {
                  setEmail(e.target.value)
                }}
                autoComplete="email"
              />
              <Input
                id="password"
                label={t('admin.auth.passwordLabel')}
                type="password"
                value={password}
                onChange={(e) => {
                  setPassword(e.target.value)
                }}
                autoComplete="current-password"
              />

              <Button type="submit" disabled={login.isPending} className="mt-stack-xs w-full">
                {login.isPending ? t('admin.auth.signingIn') : t('admin.auth.signIn')}
              </Button>
            </Stack>
          </form>
        </Stack>
      </Card>

      <p className="absolute bottom-stack-lg left-0 right-0 text-center font-chrome text-tiny tracking-wide text-text-muted">
        Powered by NENE2 · © 2026 AYANE
      </p>
    </div>
  )
}
