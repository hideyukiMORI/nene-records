import { Suspense, useEffect, useState } from 'react'
import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import {
  authStore,
  currentUserHasCapability,
  currentUserIsAdmin,
  currentUserIsSuperadmin,
  useLogout,
} from '@/entities/auth'
import { getLocalizedEntityTypeName, usePinnedEntityTypes } from '@/entities/entity-type'
import { LOCALES, SUPPORTED_LOCALE_IDS, useTranslation } from '@/shared/i18n'
import { useChromeRail } from '@/shared/lib/chrome-rail'
import { useMediaQuery } from '@/shared/lib/use-media-query'
import { NeneMark, ToastProvider } from '@/shared/ui'
import {
  IconBuilding,
  IconChevronRight,
  IconFileText,
  IconGlobe,
  IconHome,
  IconImage,
  IconKey,
  IconLayers,
  IconLayout,
  IconLink,
  IconLogOut,
  IconMenu,
  IconMessageCircle,
  IconSearch,
  IconSettings,
  IconTag,
  IconUsers,
  IconBell,
  IconWebhook,
  IconX,
} from '@/shared/ui/icons/Icons'

interface NavItemProps {
  to: string
  end?: boolean
  icon: React.ReactNode
  label: string
  onClick?: () => void
  /** Icon-only rail mode (preview): hide the label and centre the icon. */
  rail?: boolean
}

function NavItem({ to, end, icon, label, onClick, rail = false }: NavItemProps) {
  return (
    <NavLink
      to={to}
      {...(end !== undefined ? { end } : {})}
      {...(onClick !== undefined ? { onClick } : {})}
      {...(rail ? { title: label, 'aria-label': label } : {})}
      className={({ isActive }) =>
        [
          'flex items-center rounded-md py-1.5 font-chrome text-sm font-medium transition-colors duration-fast',
          rail ? 'justify-center px-0' : 'gap-2.5 px-2.5',
          isActive
            ? 'bg-sidebar-active-tint text-sidebar-active-text'
            : 'text-sidebar-text hover:bg-sidebar-hover-bg hover:text-sidebar-active-text',
        ].join(' ')
      }
    >
      {({ isActive }) => (
        <>
          <span className={['shrink-0', isActive ? 'text-accent' : 'opacity-70'].join(' ')}>
            {icon}
          </span>
          {rail ? null : <span>{label}</span>}
        </>
      )}
    </NavLink>
  )
}

/** Two-letter avatar initials from an email address (e.g. site.admin@… → "SA"). */
function userInitials(email: string): string {
  const local = email.split('@')[0] ?? email
  const parts = local.split(/[._+-]+/).filter(Boolean)
  const letters =
    parts.length >= 2 ? `${parts[0]?.[0] ?? ''}${parts[1]?.[0] ?? ''}` : local.slice(0, 2)
  return letters.toUpperCase()
}

/** Humanised display name from an email's local part (e.g. admin@… → "Admin"). */
function userDisplayName(email: string): string {
  const local = email.split('@')[0] ?? email
  return local.charAt(0).toUpperCase() + local.slice(1)
}

export function AppShell() {
  const navigate = useNavigate()
  const { t, locale, setLocale } = useTranslation()
  const [sidebarOpen, setSidebarOpen] = useState(false)

  // Prevent body scroll when drawer is open
  useEffect(() => {
    if (sidebarOpen) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
    return () => {
      document.body.style.overflow = ''
    }
  }, [sidebarOpen])

  const logout = useLogout()
  const handleLogout = () => {
    // Clears the HttpOnly cookie server-side, then the local profile.
    logout.mutate(undefined, {
      onSettled: () => {
        void navigate('/login')
      },
    })
  }

  const session = authStore.getSession()
  const canManageTags = currentUserHasCapability('manage_tags')
  const canReadSettings = currentUserHasCapability('read_settings')
  const canManageSettings = currentUserHasCapability('manage_settings')
  const isAdmin = currentUserIsAdmin()
  const isSuperadmin = currentUserIsSuperadmin()

  const [appearanceOpen, setAppearanceOpen] = useState(true)
  const [advancedOpen, setAdvancedOpen] = useState(false)
  const pinnedEntityTypesQuery = usePinnedEntityTypes()
  const pinnedEntityTypes = pinnedEntityTypesQuery.data ?? []

  // Preview mode collapses the desktop sidebar to an icon rail. Gated to desktop
  // (≥lg) so it never fights the mobile drawer, which is unaffected.
  const railSignal = useChromeRail()
  const isDesktop = useMediaQuery('(min-width: 1024px)')
  const rail = railSignal && isDesktop

  const closeSidebar = () => {
    setSidebarOpen(false)
  }

  return (
    <ToastProvider>
      <div className="flex min-h-screen bg-surface font-sans text-text-primary">
        {/* ── Mobile top bar (hidden on lg+) ─────────────────────────────── */}
        <header className="fixed inset-x-0 top-0 z-10 flex h-14 items-center gap-3 border-b border-sidebar-border bg-sidebar-bg px-4 lg:hidden">
          <button
            type="button"
            onClick={() => {
              setSidebarOpen(true)
            }}
            aria-label={t('admin.nav.openMenu')}
            className="flex items-center justify-center rounded-md p-1.5 text-sidebar-text transition-colors hover:bg-sidebar-hover-bg hover:text-sidebar-active-text"
          >
            <IconMenu size={20} />
          </button>
          <NeneMark size={20} className="shrink-0 text-accent" />
          <span className="font-chrome text-sm font-bold tracking-tight text-sidebar-active-text">
            NeNe Records
          </span>
          <span className="rounded-sm bg-accent px-1.5 py-0.5 font-chrome text-tiny font-bold uppercase tracking-wider text-text-inverse">
            {t('admin.nav.adminBadge')}
          </span>
        </header>

        {/* ── Overlay backdrop (mobile only) ──────────────────────────────── */}
        {sidebarOpen ? (
          <div
            className="fixed inset-0 z-20 bg-scrim lg:hidden"
            aria-hidden="true"
            onClick={closeSidebar}
          />
        ) : null}

        {/* ── Sidebar ─────────────────────────────────────────────────────── */}
        <aside
          className={[
            'fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-sidebar-border bg-sidebar-bg',
            'transition-all duration-200',
            'lg:translate-x-0',
            rail ? 'lg:w-16' : 'lg:w-60',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
          ].join(' ')}
          aria-label={t('admin.nav.sidebar')}
        >
          {/* Brand */}
          <div
            className={[
              'flex h-14 shrink-0 items-center border-b border-sidebar-border',
              rail ? 'justify-center px-0' : 'gap-2 px-4',
            ].join(' ')}
          >
            <NeneMark size={22} className="shrink-0 text-accent" />
            {rail ? null : (
              <>
                <span className="flex-1 font-chrome text-sm font-bold tracking-tight text-sidebar-active-text">
                  NeNe Records
                </span>
                <span className="rounded-sm bg-accent px-1.5 py-0.5 font-chrome text-tiny font-bold uppercase tracking-wider text-text-inverse">
                  {t('admin.nav.adminBadge')}
                </span>
              </>
            )}
            {/* Close button — mobile only */}
            <button
              type="button"
              onClick={closeSidebar}
              aria-label={t('admin.nav.closeMenu')}
              className="ml-1 flex items-center justify-center rounded-md p-1 text-sidebar-text transition-colors hover:bg-sidebar-hover-bg hover:text-sidebar-active-text lg:hidden"
            >
              <IconX size={18} />
            </button>
          </div>

          {/* Nav links */}
          <nav
            className={['flex-1 overflow-y-auto py-4', rail ? 'px-2' : 'px-3'].join(' ')}
            aria-label={t('admin.nav.main')}
          >
            {/* ── Primary nav ── */}
            <ul className="space-y-0.5">
              <li>
                <NavItem
                  to="/admin"
                  end
                  icon={<IconHome size={16} />}
                  label={t('admin.nav.home')}
                  onClick={closeSidebar}
                  rail={rail}
                />
              </li>
              {/* ── Pinned content types (dynamic) ── */}
              {pinnedEntityTypes.map((entityType) => (
                <li key={entityType.id}>
                  <NavItem
                    to={`/admin/${entityType.slug}`}
                    icon={<IconFileText size={16} />}
                    label={getLocalizedEntityTypeName(entityType, locale)}
                    onClick={closeSidebar}
                    rail={rail}
                  />
                </li>
              ))}
              {pinnedEntityTypes.length > 0 && (
                <li aria-hidden="true" className="my-2 border-t border-sidebar-border opacity-50" />
              )}
              {canManageTags ? (
                <li>
                  <NavItem
                    to="/admin/tags"
                    icon={<IconTag size={16} />}
                    label={t('admin.nav.tags')}
                    onClick={closeSidebar}
                    rail={rail}
                  />
                </li>
              ) : null}
              {isAdmin ? (
                <li>
                  <NavItem
                    to="/admin/users"
                    icon={<IconUsers size={16} />}
                    label={t('admin.nav.users')}
                    onClick={closeSidebar}
                    rail={rail}
                  />
                </li>
              ) : null}
              {isAdmin ? (
                <li>
                  <NavItem
                    to="/admin/account"
                    icon={<IconKey size={16} />}
                    label={t('admin.nav.account')}
                    onClick={closeSidebar}
                    rail={rail}
                  />
                </li>
              ) : null}
              {isSuperadmin ? (
                <li>
                  <NavItem
                    to="/superadmin"
                    icon={<IconBuilding size={16} />}
                    label={t('admin.superadmin.navTitle')}
                    onClick={closeSidebar}
                    rail={rail}
                  />
                </li>
              ) : null}
              {canReadSettings ? (
                <li>
                  <NavItem
                    to="/admin/settings"
                    icon={<IconSettings size={16} />}
                    label={t('admin.nav.settings')}
                    onClick={closeSidebar}
                    rail={rail}
                  />
                </li>
              ) : null}
            </ul>

            {/* ── Appearance (collapsible, default open) ── */}
            {canManageSettings ? (
              <>
                <div className="my-4 border-t border-sidebar-border" />
                <div>
                  {rail ? null : (
                    <button
                      type="button"
                      onClick={() => {
                        setAppearanceOpen((o) => !o)
                      }}
                      className="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 font-chrome text-tiny font-bold uppercase tracking-widest text-sidebar-text-muted transition-colors hover:bg-sidebar-hover-bg hover:text-sidebar-active-text"
                      aria-expanded={appearanceOpen}
                    >
                      <IconLayout size={12} className="shrink-0" />
                      <span className="flex-1 text-left">{t('admin.nav.appearance')}</span>
                      <IconChevronRight
                        size={12}
                        className={[
                          'shrink-0 transition-transform duration-150',
                          appearanceOpen ? 'rotate-90' : '',
                        ].join(' ')}
                      />
                    </button>
                  )}
                  {appearanceOpen || rail ? (
                    <ul className="mt-0.5 space-y-0.5">
                      <li>
                        <NavItem
                          to="/admin/appearance/layout"
                          icon={<IconLayout size={16} />}
                          label={t('admin.nav.layout')}
                          onClick={closeSidebar}
                          rail={rail}
                        />
                      </li>
                      <li>
                        <NavItem
                          to="/admin/appearance/menus"
                          icon={<IconLink size={16} />}
                          label={t('admin.nav.menus')}
                          onClick={closeSidebar}
                          rail={rail}
                        />
                      </li>
                      <li>
                        <NavItem
                          to="/admin/media"
                          icon={<IconImage size={16} />}
                          label={t('admin.nav.media')}
                          onClick={closeSidebar}
                          rail={rail}
                        />
                      </li>
                      <li>
                        <NavItem
                          to="/admin/comments"
                          icon={<IconMessageCircle size={16} />}
                          label={t('admin.nav.comments')}
                          onClick={closeSidebar}
                          rail={rail}
                        />
                      </li>
                    </ul>
                  ) : null}
                </div>
              </>
            ) : null}

            {/* ── Advanced (collapsible, default closed) ── */}
            {canManageSettings ? (
              <>
                <div className="my-4 border-t border-sidebar-border" />
                <div>
                  {rail ? null : (
                    <button
                      type="button"
                      onClick={() => {
                        setAdvancedOpen((o) => !o)
                      }}
                      className="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 font-chrome text-tiny font-bold uppercase tracking-widest text-sidebar-text-muted transition-colors hover:bg-sidebar-hover-bg hover:text-sidebar-active-text"
                      aria-expanded={advancedOpen}
                    >
                      <span className="flex-1 text-left">{t('admin.nav.advanced')}</span>
                      <IconChevronRight
                        size={12}
                        className={[
                          'shrink-0 transition-transform duration-150',
                          advancedOpen ? 'rotate-90' : '',
                        ].join(' ')}
                      />
                    </button>
                  )}
                  {advancedOpen || rail ? (
                    <ul className="mt-0.5 space-y-0.5">
                      <li>
                        <NavItem
                          to="/admin/entity-types"
                          icon={<IconLayers size={16} />}
                          label={t('admin.nav.entityTypes')}
                          onClick={closeSidebar}
                          rail={rail}
                        />
                      </li>
                      <li>
                        <NavItem
                          to="/admin/webhooks"
                          icon={<IconWebhook size={16} />}
                          label={t('admin.nav.webhooks')}
                          onClick={closeSidebar}
                          rail={rail}
                        />
                      </li>
                      <li>
                        <NavItem
                          to="/admin/notifications"
                          icon={<IconBell size={16} />}
                          label={t('admin.nav.notifications')}
                          onClick={closeSidebar}
                          rail={rail}
                        />
                      </li>
                      <li>
                        <NavItem
                          to="/admin/import"
                          icon={<IconFileText size={16} />}
                          label={t('admin.nav.import')}
                          onClick={closeSidebar}
                          rail={rail}
                        />
                      </li>
                    </ul>
                  ) : null}
                </div>
              </>
            ) : null}
          </nav>

          {/* Bottom: public-site link + language + user row (redesign §06) */}
          <div
            className={[
              'flex shrink-0 flex-col gap-3 border-t border-sidebar-border',
              rail ? 'p-2' : 'p-3',
            ].join(' ')}
          >
            <NavItem
              to="/"
              icon={<IconGlobe size={16} />}
              label={t('admin.nav.publicSite')}
              onClick={closeSidebar}
              rail={rail}
            />

            {/* Language selector — hidden in the icon rail */}
            {rail ? null : (
              <select
                aria-label={t('admin.nav.language')}
                value={locale}
                onChange={(e) => {
                  setLocale(e.target.value as typeof locale)
                }}
                className="min-w-0 rounded-sm border border-sidebar-border bg-sidebar-hover-bg px-2 py-1.5 text-xs text-sidebar-text focus:outline-none focus:ring-1 focus:ring-accent"
              >
                {SUPPORTED_LOCALE_IDS.map((id) => (
                  <option key={id} value={id}>
                    {LOCALES[id].label}
                  </option>
                ))}
              </select>
            )}

            {/* User row — avatar + logout only when railed */}
            {session &&
              (rail ? (
                <div className="flex flex-col items-center gap-2">
                  <span className="rd-avatar" aria-hidden="true">
                    {userInitials(session.email)}
                  </span>
                  <button
                    type="button"
                    onClick={handleLogout}
                    aria-label={t('admin.nav.logout')}
                    title={t('admin.nav.logout')}
                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-sm border border-sidebar-border bg-sidebar-hover-bg text-sidebar-text transition-colors hover:border-danger hover:text-danger"
                  >
                    <IconLogOut size={15} />
                  </button>
                </div>
              ) : (
                <div className="pf-userrow">
                  <span className="rd-avatar" aria-hidden="true">
                    {userInitials(session.email)}
                  </span>
                  <div className="pf-userrow__id">
                    <div className="pf-userrow__n">{userDisplayName(session.email)}</div>
                    <div className="pf-userrow__e" title={session.email}>
                      {session.email}
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={handleLogout}
                    aria-label={t('admin.nav.logout')}
                    title={t('admin.nav.logout')}
                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-sm border border-sidebar-border bg-sidebar-hover-bg text-sidebar-text transition-colors hover:border-danger hover:text-danger"
                  >
                    <IconLogOut size={15} />
                  </button>
                </div>
              ))}
          </div>
        </aside>

        {/* ── Main content ────────────────────────────────────────────────── */}
        <main
          className={[
            'min-w-0 flex-1 pb-8 pt-14 transition-all duration-200 lg:pt-0',
            rail ? 'lg:ml-16' : 'lg:ml-60',
          ].join(' ')}
        >
          {/* Quiet topbar — search + notifications (desktop only) */}
          <div className="hidden items-center gap-3 border-b border-border bg-surface px-6 py-2.5 lg:flex">
            <label className="flex max-w-md flex-1 items-center gap-2 rounded-sm border border-border bg-surface-raised px-3 py-1.5 text-text-muted">
              <IconSearch size={15} className="shrink-0" />
              <input
                type="search"
                placeholder={t('admin.topbar.searchPlaceholder')}
                className="min-w-0 flex-1 bg-transparent text-sm text-text-primary placeholder:text-text-muted focus:outline-none"
              />
              <span className="shrink-0 rounded-sm border border-border bg-surface-overlay px-1.5 py-0.5 font-mono text-tiny text-text-muted">
                ⌘K
              </span>
            </label>
            <div className="flex-1" />
            <button
              type="button"
              aria-label={t('admin.nav.notifications')}
              onClick={() => {
                void navigate('/admin/notifications')
              }}
              className="flex h-8 w-8 items-center justify-center rounded-sm border border-border bg-surface-raised text-text-muted transition-colors hover:text-text-primary"
            >
              <IconBell size={16} />
            </button>
          </div>
          {session?.emailVerified === false && (
            <div
              role="status"
              className="border-b border-warn bg-warn/10 px-4 py-2.5 text-caption font-medium text-text-primary sm:px-6"
            >
              {t('admin.emailBanner.unverified')}
            </div>
          )}
          <div className="px-4 py-6 sm:px-6 sm:py-8">
            <Suspense fallback={null}>
              <Outlet />
            </Suspense>
          </div>
        </main>

        {/* ── Footer (fixed bottom-right) ─────────────────────────────────── */}
        <footer className="fixed bottom-0 right-0 pb-3 pl-4 pr-5 pt-2">
          <p className="font-chrome text-tiny tracking-wide text-text-muted">
            Powered by NENE2 · © 2026 AYANE
          </p>
        </footer>
      </div>
    </ToastProvider>
  )
}
