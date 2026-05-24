import { Link, Outlet } from 'react-router-dom'
import { Text } from '@/shared/ui'

export function PublicShell() {
  return (
    <div className="min-h-screen bg-surface font-sans text-text-primary">
      <header className="border-b border-border bg-surface-raised shadow-sm">
        <div className="mx-auto max-w-3xl px-inline-md py-stack-md">
          <Link to="/">
            <Text as="span" variant="heading-sm">
              NeNe Records
            </Text>
          </Link>
        </div>
      </header>
      <main className="mx-auto max-w-3xl px-inline-md py-stack-lg">
        <Outlet />
      </main>
    </div>
  )
}
