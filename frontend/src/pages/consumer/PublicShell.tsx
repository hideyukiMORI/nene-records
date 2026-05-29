import { useEffect, useMemo } from 'react'
import './consumer-theme.css'
import { Link, Outlet } from 'react-router-dom'
import { usePublicNavigationItems } from '@/entities/navigation-item'
import { publicSettingsToMap, usePublicSettings } from '@/entities/setting'
import { NeneMark, Stack, Text } from '@/shared/ui'

function useSiteDocumentMeta(siteName: string, metaDescription: string): void {
  useEffect(() => {
    if (siteName !== '') {
      document.title = siteName
    }

    let meta = document.querySelector('meta[name="description"]')

    if (metaDescription === '') {
      meta?.remove()
      return
    }

    if (meta === null) {
      meta = document.createElement('meta')
      meta.setAttribute('name', 'description')
      document.head.appendChild(meta)
    }

    meta.setAttribute('content', metaDescription)
  }, [siteName, metaDescription])
}

export function PublicShell() {
  const publicSettingsQuery = usePublicSettings()
  const navigationQuery = usePublicNavigationItems()
  const navItems = useMemo(() => navigationQuery.data?.items ?? [], [navigationQuery.data?.items])
  const settings = useMemo(
    () => publicSettingsToMap(publicSettingsQuery.data?.items ?? []),
    [publicSettingsQuery.data?.items],
  )

  const siteName = settings.site_name ?? 'NeNe Records'
  const tagline = settings.tagline ?? ''
  const metaDescription = settings.default_meta_description ?? ''
  const footerMarkdown = settings.footer_markdown ?? ''

  useSiteDocumentMeta(siteName, metaDescription)

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
                {siteName}
              </Text>
              {tagline !== '' ? (
                <Text as="span" muted variant="caption">
                  {tagline}
                </Text>
              ) : null}
            </Stack>
          </Link>
          {navItems.length > 0 ? (
            <nav aria-label="Site navigation">
              <Stack direction="horizontal" gap="sm">
                {navItems.map((item) => (
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
      <main className="mx-auto w-full max-w-3xl flex-1 px-inline-md py-stack-lg">
        <Outlet context={{ siteName, metaDescription }} />
      </main>
      {footerMarkdown !== '' ? (
        <footer className="border-t border-border bg-surface-raised">
          <div className="mx-auto max-w-3xl px-inline-md py-stack-md">
            <div className="whitespace-pre-wrap font-sans text-body text-text-muted">
              {footerMarkdown}
            </div>
          </div>
        </footer>
      ) : null}
    </div>
  )
}
