import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { authStore, currentUserHasCapability } from '@/entities/auth'
import { LOCALES, SUPPORTED_LOCALE_IDS, useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

const navLinkClass = ({ isActive }: { isActive: boolean }): string =>
  [
    'rounded-md px-inline-sm py-stack-xs text-body font-medium transition-colors duration-fast',
    isActive ? 'bg-surface-overlay text-text-primary' : 'text-text-muted hover:text-text-primary',
  ].join(' ')

export function AppShell() {
  const navigate = useNavigate()
  const { t, locale, setLocale } = useTranslation()

  const handleLogout = () => {
    authStore.clearSession()
    void navigate('/login')
  }

  const session = authStore.getSession()
  const canManageTags = currentUserHasCapability('manage_tags')
  const canReadSettings = currentUserHasCapability('read_settings')

  return (
    <div className="min-h-screen bg-surface font-sans text-text-primary">
      <header className="border-b border-border bg-surface-raised shadow-sm">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-inline-md py-stack-md">
          <Text as="span" variant="heading-sm">
            NeNe Records Admin
          </Text>
          <nav aria-label="Main">
            <Stack direction="horizontal" gap="sm">
              <NavLink to="/" className={navLinkClass} end>
                {t('admin.nav.home')}
              </NavLink>
              <NavLink to="/entity-types" className={navLinkClass}>
                {t('admin.nav.entityTypes')}
              </NavLink>
              {canManageTags ? (
                <NavLink to="/tags" className={navLinkClass}>
                  {t('admin.nav.tags')}
                </NavLink>
              ) : null}
              {canReadSettings ? (
                <NavLink to="/settings" className={navLinkClass}>
                  {t('admin.nav.settings')}
                </NavLink>
              ) : null}
              <NavLink to="/view" className={navLinkClass}>
                {t('admin.nav.publicSite')}
              </NavLink>
            </Stack>
          </nav>
          <Stack direction="horizontal" gap="sm">
            {session && (
              <Text muted className="hidden text-sm sm:block">
                {session.email}
              </Text>
            )}
            <select
              aria-label="Language"
              value={locale}
              onChange={(e) => {
                setLocale(e.target.value as typeof locale)
              }}
              className="rounded-md border border-border bg-surface-raised px-inline-sm py-stack-xs font-sans text-caption text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus"
            >
              {SUPPORTED_LOCALE_IDS.map((id) => (
                <option key={id} value={id}>
                  {LOCALES[id].label}
                </option>
              ))}
            </select>
            <Button variant="ghost" size="sm" onClick={handleLogout}>
              {t('admin.nav.logout')}
            </Button>
          </Stack>
        </div>
      </header>
      <main className="mx-auto max-w-5xl px-inline-md py-stack-lg">
        <Outlet />
      </main>
    </div>
  )
}
