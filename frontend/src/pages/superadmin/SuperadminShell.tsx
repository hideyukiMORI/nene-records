import { Suspense } from 'react'
import { Link, NavLink, Outlet, Navigate } from 'react-router-dom'
import { currentUserIsSuperadmin } from '@/entities/auth'
import { IconBuilding, IconDatabase, IconHome, IconSettings } from '@/shared/ui/icons/Icons'

interface SuperadminNavItemProps {
  to: string
  icon: React.ReactNode
  label: string
}

function SuperadminNavItem({ to, icon, label }: SuperadminNavItemProps) {
  return (
    <NavLink
      to={to}
      className={({ isActive }) =>
        [
          'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
          isActive
            ? 'bg-accent text-white'
            : 'text-text-secondary hover:bg-surface-hover hover:text-text-primary',
        ].join(' ')
      }
    >
      <span className="shrink-0">{icon}</span>
      <span>{label}</span>
    </NavLink>
  )
}

export function SuperadminShell() {
  if (!currentUserIsSuperadmin()) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <div className="flex min-h-screen bg-surface">
      {/* Sidebar */}
      <aside className="w-56 shrink-0 border-r border-border bg-surface-raised">
        <div className="flex h-14 items-center border-b border-border px-4">
          <span className="text-sm font-semibold text-accent">Superadmin</span>
        </div>
        <nav className="p-3 space-y-1">
          <SuperadminNavItem
            to="/superadmin/organizations"
            icon={<IconBuilding size={16} />}
            label="Organizations"
          />
          <SuperadminNavItem
            to="/superadmin/data-migration"
            icon={<IconDatabase size={16} />}
            label="Data Migration"
          />
          <SuperadminNavItem
            to="/superadmin/settings"
            icon={<IconSettings size={16} />}
            label="Settings"
          />
        </nav>
        <div className="absolute bottom-0 left-0 w-56 border-t border-border p-3">
          <Link
            to="/admin"
            className="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-secondary hover:text-text-primary"
          >
            <IconHome size={16} />
            <span>Back to Admin</span>
          </Link>
        </div>
      </aside>

      {/* Main content */}
      <main className="flex-1 overflow-auto">
        <div className="mx-auto max-w-5xl p-8">
          <Suspense fallback={null}>
            <Outlet />
          </Suspense>
        </div>
      </main>
    </div>
  )
}
