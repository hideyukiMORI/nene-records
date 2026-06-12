import { useEffect, useMemo } from 'react'
import './consumer-theme.css'
import { Outlet } from 'react-router-dom'
import { usePublicNavigationItems } from '@/entities/navigation-item'
import { publicSettingsToMap, usePublicSettings } from '@/entities/setting'
import type { PublicSite } from './public-site-context'

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

  const site: PublicSite = {
    siteName: settings.site_name ?? 'NeNe Records',
    tagline: settings.tagline ?? '',
    metaDescription: settings.default_meta_description ?? '',
    footerMarkdown: settings.footer_markdown ?? '',
    navItems,
  }

  useSiteDocumentMeta(site.siteName, site.metaDescription)

  // PublicShell only resolves site-wide data and the document meta. The visual
  // scaffold (header / footer / theme) is rendered per page by PublicLayout, so
  // a record can pick its own layout — including `bare`, which has no chrome.
  return <Outlet context={site} />
}
