import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import type { PublicLayoutKey } from '@/shared/lib/resolve-layout'
import { NeneMark, Stack, Text } from '@/shared/ui'
import type { PublicSite } from './public-site-context'

export interface PublicLayoutProps {
  variant: PublicLayoutKey
  site: PublicSite
  children: ReactNode
}

/**
 * PublicLayout — renders the public-page scaffold for a resolved layout preset.
 *
 * - standard: header / single column (max-w-3xl) / footer
 * - full:     header / full-width content / footer
 * - bare:     no header/footer, no theme — content is rendered as-is so a record
 *             can ship a fully custom page (CSS in content).
 *
 * Does not: fetch data or resolve which layout to use (the page does that).
 */
export function PublicLayout({ variant, site, children }: PublicLayoutProps) {
  // Fully custom page: escape the theme and all chrome.
  if (variant === 'bare') {
    return <>{children}</>
  }

  const mainClassName =
    variant === 'full'
      ? 'w-full flex-1 px-inline-md py-stack-lg'
      : 'mx-auto w-full max-w-3xl flex-1 px-inline-md py-stack-lg'

  return (
    <div
      data-theme="consumer"
      className="flex min-h-screen flex-col bg-surface font-sans text-text-primary"
    >
      <header className="border-b border-border bg-surface-raised shadow-sm">
        <div className="mx-auto flex max-w-3xl items-center justify-between px-inline-md py-stack-md">
          <Link to="/" className="flex items-center gap-inline-sm">
            <NeneMark size={22} className="shrink-0 text-accent" />
            <Stack gap="xs">
              <Text as="span" variant="heading-sm">
                {site.siteName}
              </Text>
              {site.tagline !== '' ? (
                <Text as="span" muted variant="caption">
                  {site.tagline}
                </Text>
              ) : null}
            </Stack>
          </Link>
          {site.navItems.length > 0 ? (
            <nav aria-label="Site navigation">
              <Stack direction="horizontal" gap="sm">
                {site.navItems.map((item) => (
                  <Link
                    key={item.id}
                    to={item.url}
                    className="font-sans text-body text-text-muted transition-colors duration-fast hover:text-text-primary"
                  >
                    {item.label}
                  </Link>
                ))}
              </Stack>
            </nav>
          ) : null}
        </div>
      </header>
      <main className={mainClassName}>{children}</main>
      {site.footerMarkdown !== '' ? (
        <footer className="border-t border-border bg-surface-raised">
          <div className="mx-auto max-w-3xl px-inline-md py-stack-md">
            <div className="whitespace-pre-wrap font-sans text-body text-text-muted">
              {site.footerMarkdown}
            </div>
          </div>
        </footer>
      ) : null}
    </div>
  )
}
