import { useEffect, useMemo } from 'react'
import './consumer-theme.css'
import './public-fonts'
import { Outlet } from 'react-router-dom'
import { usePublicNavigationItems } from '@/entities/navigation-item'
import { publicSettingsToMap, usePublicSettings } from '@/entities/setting'
import {
  readStoredActiveTheme,
  resolvePublicThemeId,
  storeActiveTheme,
} from '@/shared/lib/public-themes'
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

  // Apply the last-known theme synchronously on first paint, then reconcile with
  // the fetched setting — avoids a default→saved theme flash (FOUC) while the
  // public settings request is in flight. Derived (no state): use the stored
  // theme until settings settle, then the resolved value (which we persist for
  // the next first paint).
  const settingsSettled = publicSettingsQuery.data !== undefined
  const resolvedTheme = resolvePublicThemeId(settings.active_theme)
  const activeTheme = settingsSettled ? resolvedTheme : readStoredActiveTheme()
  useEffect(() => {
    if (settingsSettled) {
      storeActiveTheme(resolvedTheme)
    }
  }, [settingsSettled, resolvedTheme])

  const site: PublicSite = {
    siteName: settings.site_name ?? 'NeNe Records',
    tagline: settings.tagline ?? '',
    metaDescription: settings.default_meta_description ?? '',
    footerMarkdown: settings.footer_markdown ?? '',
    navItems,
    activeTheme,
  }

  useSiteDocumentMeta(site.siteName, site.metaDescription)

  // PublicShell only resolves site-wide data and the document meta. The visual
  // scaffold (header / footer / drawer / theme) is the magazine PublicSiteShell,
  // rendered per page so a record can still opt into a chrome-less `bare` layout.
  return <Outlet context={site} />
}
