import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { authStore, currentUserHasCapability } from '@/entities/auth'
import { LOCALES, SUPPORTED_LOCALE_IDS, useTranslation } from '@/shared/i18n'
import { useTheme } from '@/shared/theme'
import {
  IconHome,
  IconLayers,
  IconTag,
  IconLink,
  IconSettings,
  IconGlobe,
  IconSun,
  IconMoon,
  IconLogOut,
} from '@/shared/ui/icons/Icons'

interface NavItemProps {
  to: string
  end?: boolean
  icon: React.ReactNode
  label: string
}

function NavItem({ to, end, icon, label }: NavItemProps) {
  return (
    <NavLink
      to={to}
      end={end}
      className={({ isActive }) =>
        [
          'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors duration-fast',
          isActive
            ? 'bg-sidebar-active-bg text-sidebar-active-text'
            : 'text-sidebar-text hover:bg-sidebar-hover-bg hover:text-sidebar-active-text',
        ].join(' ')
      }
    >
      <span className="shrink-0 opacity-75">{icon}</span>
      <span>{label}</span>
    </NavLink>
  )
}

export function AppShell() {
  const navigate = useNavigate()
  const { t, locale, setLocale } = useTranslation()
  const { theme, toggleTheme } = useTheme()

  const handleLogout = () => {
    authStore.clearSession()
    void navigate('/login')
  }

  const session = authStore.getSession()
  const canManageTags = currentUserHasCapability('manage_tags')
  const canReadSettings = currentUserHasCapability('read_settings')
  const canManageSettings = currentUserHasCapability('manage_settings')

  return (
    <div className="flex min-h-screen bg-surface font-sans text-text-primary">
      {/* ── Sidebar ────────────────────────────────────────────────────── */}
      <aside
        className="fixed inset-y-0 left-0 flex w-60 flex-col border-r border-sidebar-border bg-sidebar-bg"
        aria-label="Sidebar"
      >
        {/* Brand */}
        <div className="flex h-14 shrink-0 items-center gap-2 border-b border-sidebar-border px-4">
          <span className="text-sm font-semibold tracking-wide text-sidebar-active-text">
            NeNe Records
          </span>
          <span className="rounded bg-accent px-1.5 py-0.5 text-caption font-semibold uppercase tracking-wider text-white">
            Admin
          </span>
        </div>

        {/* Nav links */}
        <nav className="flex-1 overflow-y-auto px-3 py-4" aria-label="Main">
          <ul className="space-y-0.5">
            <li>
              <NavItem to="/" end icon={<IconHome size={16} />} label={t('admin.nav.home')} />
            </li>
            <li>
              <NavItem
                to="/entity-types"
                icon={<IconLayers size={16} />}
                label={t('admin.nav.entityTypes')}
              />
            </li>
            {canManageTags ? (
              <li>
                <NavItem to="/tags" icon={<IconTag size={16} />} label={t('admin.nav.tags')} />
              </li>
            ) : null}
            {canManageSettings ? (
              <li>
                <NavItem
                  to="/navigation"
                  icon={<IconLink size={16} />}
                  label={t('admin.nav.navigation')}
                />
              </li>
            ) : null}
            {canReadSettings ? (
              <li>
                <NavItem
                  to="/settings"
                  icon={<IconSettings size={16} />}
                  label={t('admin.nav.settings')}
                />
              </li>
            ) : null}
          </ul>

          <div className="my-4 border-t border-sidebar-border" />

          <ul className="space-y-0.5">
            <li>
              <NavItem
                to="/view"
                icon={<IconGlobe size={16} />}
                label={t('admin.nav.publicSite')}
              />
            </li>
          </ul>
        </nav>

        {/* Bottom: user info + controls */}
        <div className="shrink-0 space-y-2 border-t border-sidebar-border p-3">
          {session && (
            <div className="truncate px-1 text-xs text-sidebar-text-muted" title={session.email}>
              {session.email}
            </div>
          )}

          <div className="flex items-center gap-1">
            {/* Language selector */}
            <select
              aria-label="Language"
              value={locale}
              onChange={(e) => {
                setLocale(e.target.value as typeof locale)
              }}
              className="min-w-0 flex-1 rounded-md border border-sidebar-border bg-sidebar-active-bg px-2 py-1.5 text-xs text-sidebar-text focus:outline-none focus:ring-1 focus:ring-accent"
            >
              {SUPPORTED_LOCALE_IDS.map((id) => (
                <option key={id} value={id}>
                  {LOCALES[id].label}
                </option>
              ))}
            </select>

            {/* Theme toggle */}
            <button
              type="button"
              onClick={toggleTheme}
              aria-label={
                theme === 'dark' ? t('admin.theme.toggleLight') : t('admin.theme.toggleDark')
              }
              className="flex shrink-0 items-center justify-center rounded-md border border-sidebar-border bg-sidebar-active-bg p-1.5 text-sidebar-text transition-colors hover:bg-sidebar-hover-bg hover:text-sidebar-active-text"
            >
              {theme === 'dark' ? <IconSun size={15} /> : <IconMoon size={15} />}
            </button>

            {/* Logout */}
            <button
              type="button"
              onClick={handleLogout}
              aria-label={t('admin.nav.logout')}
              title={t('admin.nav.logout')}
              className="flex shrink-0 items-center justify-center rounded-md border border-sidebar-border bg-sidebar-active-bg p-1.5 text-sidebar-text transition-colors hover:border-red-800 hover:bg-red-950/60 hover:text-red-300"
            >
              <IconLogOut size={15} />
            </button>
          </div>
        </div>
      </aside>

      {/* ── Main content ───────────────────────────────────────────────── */}
      <main className="ml-60 min-w-0 flex-1">
        <div className="mx-auto max-w-5xl px-6 py-8">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
