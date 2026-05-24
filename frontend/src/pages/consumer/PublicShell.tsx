import { useEffect, useMemo } from 'react'
import './consumer-theme.css'
import { Link, Outlet } from 'react-router-dom'
import { publicSettingsToMap, usePublicSettings } from '@/entities/setting'
import { Stack, Text } from '@/shared/ui'

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
        <div className="mx-auto max-w-3xl px-inline-md py-stack-md">
          <Link to="/view">
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
