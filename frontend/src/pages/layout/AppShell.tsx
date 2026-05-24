import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { authStore, currentUserHasCapability } from '@/entities/auth'
import { Button, Stack, Text } from '@/shared/ui'

const navLinkClass = ({ isActive }: { isActive: boolean }): string =>
  [
    'rounded-md px-inline-sm py-stack-xs text-body font-medium transition-colors duration-fast',
    isActive ? 'bg-surface-overlay text-text-primary' : 'text-text-muted hover:text-text-primary',
  ].join(' ')

export function AppShell() {
  const navigate = useNavigate()

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
                Home
              </NavLink>
              <NavLink to="/entity-types" className={navLinkClass}>
                Entity types
              </NavLink>
              {canManageTags ? (
                <NavLink to="/tags" className={navLinkClass}>
                  Tags
                </NavLink>
              ) : null}
              {canReadSettings ? (
                <NavLink to="/settings" className={navLinkClass}>
                  Settings
                </NavLink>
              ) : null}
              <NavLink to="/view" className={navLinkClass}>
                Public site
              </NavLink>
            </Stack>
          </nav>
          <Stack direction="horizontal" gap="sm">
            {session && (
              <Text muted className="hidden text-sm sm:block">
                {session.email}
              </Text>
            )}
            <Button variant="ghost" size="sm" onClick={handleLogout}>
              ログアウト
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
