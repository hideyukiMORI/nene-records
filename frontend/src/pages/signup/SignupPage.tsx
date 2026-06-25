import { useState, type ReactNode, type SyntheticEvent } from 'react'
import { useSignup, type SignupResponseDto } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Input, NeneMark, Stack, Text } from '@/shared/ui'

/**
 * Public self-serve signup (subdomain SaaS apex). Provisions a tenant, then hands
 * the new admin off to their own subdomain to sign in — the session cookie is
 * host-only, so it never crosses into another tenant.
 */
export function SignupPage() {
  const [organizationName, setOrganizationName] = useState('')
  const [slug, setSlug] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [done, setDone] = useState<SignupResponseDto | null>(null)
  const signup = useSignup()
  const { t } = useTranslation()

  // At the apex, the current host IS the SaaS base domain.
  const baseDomain = typeof window !== 'undefined' ? window.location.hostname : 'nene-records.com'
  const previewUrl = `${slug || t('admin.signup.slugPlaceholder')}.${baseDomain}`

  const handleSubmit = (e: SyntheticEvent) => {
    e.preventDefault()
    signup.mutate(
      { organization_name: organizationName, slug, email, password },
      {
        onSuccess: (dto) => {
          setDone(dto)
        },
      },
    )
  }

  if (done !== null) {
    const tenantUrl = `https://${done.slug}.${baseDomain}`
    return (
      <Centered>
        <Card padding="lg" className="w-full max-w-sm">
          <Stack gap="lg">
            <Stack gap="xs">
              <Text as="h1" variant="heading-md">
                {t('admin.signup.successTitle')}
              </Text>
              <Text muted>
                {t('admin.signup.successBody', { url: `${done.slug}.${baseDomain}` })}
              </Text>
            </Stack>
            <Button
              type="button"
              className="w-full"
              onClick={() => {
                window.location.href = `${tenantUrl}/login`
              }}
            >
              {t('admin.signup.goToLogin')}
            </Button>
          </Stack>
        </Card>
      </Centered>
    )
  }

  const isConflict = signup.error?.status === 409

  return (
    <Centered>
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
                {t('admin.signup.appTitle')}
              </Text>
              <Text muted>{t('admin.signup.subtitle')}</Text>
            </Stack>
          </Stack>

          {signup.isError && (
            <div
              role="alert"
              className="rounded-sm border border-danger bg-danger/10 px-inline-sm py-stack-xs text-caption font-medium text-danger"
            >
              {isConflict ? t('admin.signup.errorTaken') : t('admin.signup.errorGeneric')}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <Stack gap="md">
              <Input
                id="organization_name"
                label={t('admin.signup.orgNameLabel')}
                value={organizationName}
                onChange={(e) => {
                  setOrganizationName(e.target.value)
                }}
                autoComplete="organization"
              />
              <Stack gap="xs">
                <Input
                  id="slug"
                  label={t('admin.signup.slugLabel')}
                  value={slug}
                  onChange={(e) => {
                    setSlug(e.target.value.toLowerCase())
                  }}
                  autoComplete="off"
                />
                <Text variant="caption" muted>
                  {t('admin.signup.slugHint', { url: previewUrl })}
                </Text>
              </Stack>
              <Input
                id="email"
                label={t('admin.signup.emailLabel')}
                type="email"
                value={email}
                onChange={(e) => {
                  setEmail(e.target.value)
                }}
                autoComplete="email"
              />
              <Input
                id="password"
                label={t('admin.signup.passwordLabel')}
                type="password"
                value={password}
                onChange={(e) => {
                  setPassword(e.target.value)
                }}
                autoComplete="new-password"
              />

              <Button type="submit" disabled={signup.isPending} className="mt-stack-xs w-full">
                {signup.isPending ? t('admin.signup.submitting') : t('admin.signup.submit')}
              </Button>
            </Stack>
          </form>
        </Stack>
      </Card>
    </Centered>
  )
}

function Centered({ children }: { children: ReactNode }) {
  return (
    <div className="relative flex min-h-screen items-center justify-center bg-surface px-inline-md">
      {children}
    </div>
  )
}
