import { Suspense, useEffect, useMemo } from 'react'
import './consumer-theme.css'
import './public-fonts'
import { Outlet, ScrollRestoration } from 'react-router-dom'
import { ConsentBanner } from '@/features/consent-banner'
import { usePublicNavigationItems } from '@/entities/navigation-item'
import { publicSettingsToMap, usePublicSettings } from '@/entities/setting'
import { usePublicThemes } from '@/entities/theme'
import { parseHeaderConfig } from '@/shared/lib/header-config'
import { useAnalyticsPageView } from '@/shared/lib/use-analytics-page-view'
import { resolveWebAnalytics } from '@/shared/lib/web-analytics'
import { parseLayoutConfig } from '@/shared/lib/layout-config'
import {
  buildThemeStylesheet,
  readStoredRuntimeTheme,
  type RuntimeTheme,
  storeRuntimeTheme,
} from '@/shared/lib/runtime-themes'
import {
  readStoredActiveTheme,
  resolvePublicThemeId,
  storeActiveTheme,
} from '@/shared/lib/public-themes'
import {
  flagAttrsForTheme,
  overrideCssForTheme,
  parseThemeOverrides,
  readStoredThemeOverridesRaw,
  resolveFlagAttrs,
  storeThemeOverridesRaw,
  type ThemeLogo,
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

  // GA4 / GTM (PR-A1 injects the loader + consent default server-side). Here the
  // SPA reports client-side navigations and offers the consent prompt.
  const analytics = useMemo(() => resolveWebAnalytics(settings), [settings])
  useAnalyticsPageView(analytics)

  // Apply the last-known theme synchronously on first paint, then reconcile with
  // the fetched setting — avoids a default→saved theme flash (FOUC) while the
  // public settings request is in flight. Derived (no state): use the stored
  // theme until settings settle, then the resolved value (which we persist for
  // the next first paint).
  // Runtime (data-driven) themes registered via the API. The active theme may
  // be one of these instead of a built-in; if so we apply its manifest as a
  // scoped stylesheet rather than relying on static `[data-theme]` CSS.
  const publicThemesQuery = usePublicThemes()
  const runtimeThemes = useMemo(
    () => (publicThemesQuery.data?.items ?? []) as RuntimeTheme[],
    [publicThemesQuery.data?.items],
  )

  // Resolving a runtime active theme needs both the settings (active_theme) and
  // the runtime theme list. Until both settle we use the last-known cached
  // values so a runtime active theme doesn't flash the default on first paint.
  const settingsSettled = publicSettingsQuery.data !== undefined
  const themesSettled = publicThemesQuery.data !== undefined
  const bothSettled = settingsSettled && themesSettled

  const runtimeActive = bothSettled
    ? runtimeThemes.find((theme) => theme.theme_key === settings.active_theme)
    : undefined
  // A runtime active theme keeps its own key; otherwise resolve to a built-in.
  const resolvedTheme = runtimeActive
    ? runtimeActive.theme_key
    : resolvePublicThemeId(settings.active_theme)
  const activeTheme = bothSettled ? resolvedTheme : readStoredActiveTheme()
  // Customizer overrides: stored raw applied on first paint, fetched value after.
  const overridesRaw = bothSettled ? settings.theme_overrides : readStoredThemeOverridesRaw()

  // Runtime theme: emit its full token set as a scoped stylesheet, and apply its
  // structural flags as data-* attributes (manifest flags win, like a theme's
  // built-in flags). Built-in themes keep static CSS, so these stay empty. The
  // live values are authoritative once both queries settle; before that we read
  // the FOUC cache.
  const liveRuntimeThemeCss = runtimeActive
    ? buildThemeStylesheet(runtimeActive.theme_key, runtimeActive.manifest)
    : ''
  const liveRuntimeFlagAttrs = runtimeActive
    ? resolveFlagAttrs(runtimeActive.manifest.flags, undefined)
    : {}
  const cachedRuntimeTheme = readStoredRuntimeTheme()
  const runtimeThemeCss = bothSettled ? liveRuntimeThemeCss : cachedRuntimeTheme.css
  const runtimeFlagAttrs = bothSettled ? liveRuntimeFlagAttrs : cachedRuntimeTheme.flags

  const liveRuntimeFlagsJson = JSON.stringify(liveRuntimeFlagAttrs)
  useEffect(() => {
    if (bothSettled) {
      storeActiveTheme(resolvedTheme)
      storeThemeOverridesRaw(settings.theme_overrides)
      storeRuntimeTheme({
        css: liveRuntimeThemeCss,
        flags: JSON.parse(liveRuntimeFlagsJson) as Record<string, string>,
      })
    }
  }, [
    bothSettled,
    resolvedTheme,
    settings.theme_overrides,
    liveRuntimeThemeCss,
    liveRuntimeFlagsJson,
  ])

  // Per-theme logo override (#372): the public endpoint has already resolved the
  // media ids to URLs, so pick out the string values for the active theme.
  const themeLogoRef = parseThemeOverrides(overridesRaw)[activeTheme]?.images?.logo
  const themeLogo: ThemeLogo = {
    light: typeof themeLogoRef?.light === 'string' ? themeLogoRef.light : undefined,
    dark: typeof themeLogoRef?.dark === 'string' ? themeLogoRef.dark : undefined,
  }

  const site: PublicSite = {
    siteName: settings.site_name ?? 'NeNe Records',
    tagline: settings.tagline ?? '',
    metaDescription: settings.default_meta_description ?? '',
    footerMarkdown: settings.footer_markdown ?? '',
    logo: settings.logo_media_id ?? '',
    themeLogo,
    copyrightText: settings.copyright_text ?? '',
    homeLayout: parseLayoutConfig(settings.layout_config).home,
    navItems,
    activeTheme,
    themeOverrideCss: overrideCssForTheme(overridesRaw, activeTheme),
    themeFlagAttrs: { ...flagAttrsForTheme(overridesRaw, activeTheme), ...runtimeFlagAttrs },
    runtimeThemeCss,
    headerConfig: parseHeaderConfig(settings.header_config),
    homeHero: settings.home_hero ?? '',
    frontPagePath: settings.front_page ?? '',
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
      <Suspense fallback={null}>
        <Outlet context={site} />
      </Suspense>
      <ConsentBanner config={analytics} />
    </>
  )
}
