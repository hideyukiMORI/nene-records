import { useState, type SyntheticEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { useLogin } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

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
          void navigate('/')
        },
      },
    )
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-inline-md">
      <div className="w-full max-w-sm rounded-lg border border-border bg-surface-raised p-stack-lg shadow-sm">
        <Stack gap="lg">
          <Stack gap="xs">
            <Text as="h1" variant="heading-md">
              {t('admin.auth.appTitle')}
            </Text>
            <Text muted>{t('admin.auth.subtitle')}</Text>
          </Stack>

          {login.isError && (
            <div
              role="alert"
              className="rounded-md border border-red-200 bg-red-50 px-inline-sm py-stack-xs text-sm text-red-700"
            >
              {t('admin.auth.invalidCredentials')}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <Stack gap="md">
              <Stack gap="xs">
                <label htmlFor="email" className="text-sm font-medium text-text-primary">
                  {t('admin.auth.emailLabel')}
                </label>
                <Input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value)
                  }}
                  placeholder={t('admin.auth.emailPlaceholder')}
                  required
                />
              </Stack>

              <Stack gap="xs">
                <label htmlFor="password" className="text-sm font-medium text-text-primary">
                  {t('admin.auth.passwordLabel')}
                </label>
                <Input
                  id="password"
                  type="password"
                  value={password}
                  onChange={(e) => {
                    setPassword(e.target.value)
                  }}
                  placeholder={t('admin.auth.passwordPlaceholder')}
                  required
                />
              </Stack>

              <Button type="submit" disabled={login.isPending} className="w-full">
                {login.isPending ? t('admin.auth.signingIn') : t('admin.auth.signIn')}
              </Button>
            </Stack>
          </form>
        </Stack>
      </div>
    </div>
  )
}
