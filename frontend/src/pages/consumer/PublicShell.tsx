import { useEffect, useMemo } from 'react'
import './consumer-theme.css'
import './public-fonts'
import { Outlet, ScrollRestoration } from 'react-router-dom'
import { usePublicNavigationItems } from '@/entities/navigation-item'
import { publicSettingsToMap, usePublicSettings } from '@/entities/setting'
import { parseHeaderConfig } from '@/shared/lib/header-config'
import { parseLayoutConfig } from '@/shared/lib/layout-config'
import {
  readStoredActiveTheme,
  resolvePublicThemeId,
  storeActiveTheme,
} from '@/shared/lib/public-themes'
import {
  flagAttrsForTheme,
  overrideCssForTheme,
  readStoredThemeOverridesRaw,
  storeThemeOverridesRaw,
} from '@/shared/lib/theme-customization'
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
  // Customizer overrides: stored raw applied on first paint, fetched value after.
  const overridesRaw = settingsSettled ? settings.theme_overrides : readStoredThemeOverridesRaw()
  useEffect(() => {
    if (settingsSettled) {
      storeActiveTheme(resolvedTheme)
      storeThemeOverridesRaw(settings.theme_overrides)
    }
  }, [settingsSettled, resolvedTheme, settings.theme_overrides])

  const site: PublicSite = {
    siteName: settings.site_name ?? 'NeNe Records',
    tagline: settings.tagline ?? '',
    metaDescription: settings.default_meta_description ?? '',
    footerMarkdown: settings.footer_markdown ?? '',
    logo: settings.logo_media_id ?? '',
    copyrightText: settings.copyright_text ?? '',
    homeLayout: parseLayoutConfig(settings.layout_config).home,
    navItems,
    activeTheme,
    themeOverrideCss: overrideCssForTheme(overridesRaw, activeTheme),
    themeFlagAttrs: flagAttrsForTheme(overridesRaw, activeTheme),
    headerConfig: parseHeaderConfig(settings.header_config),
  }

  useSiteDocumentMeta(site.siteName, site.metaDescription)

  // PublicShell only resolves site-wide data and the document meta. The visual
  // scaffold (header / footer / drawer / theme) is the magazine PublicSiteShell,
  // rendered per page so a record can still opt into a chrome-less `bare` layout.
  //
  // ScrollRestoration scopes scroll handling to the public site: a new
  // navigation (e.g. into a record/page) jumps to the top, while back/forward
  // restores the previous position — without changing the admin's behaviour.
  return (
    <>
      <ScrollRestoration />
      <Outlet context={site} />
    </>
  )
}
