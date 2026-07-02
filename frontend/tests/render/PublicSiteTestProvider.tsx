import { Outlet } from 'react-router-dom'
import { DEFAULT_HEADER_CONFIG } from '@/shared/lib/header-config'
import type { PublicSite } from '@/pages/consumer/public-site-context'

const TEST_SITE: PublicSite = {
  siteName: 'Test Site',
  tagline: '',
  metaDescription: '',
  footerMarkdown: '',
  logo: '',
  copyrightText: '',
  homeLayout: { columns: 2, mainPos: 'left', swap: false },
  navItems: [],
  activeTheme: 'consumer',
  themeOverrideCss: '',
  runtimeThemeCss: '',
  themeFlagAttrs: {},
  headerConfig: DEFAULT_HEADER_CONFIG,
  homeHero: '',
  frontPagePath: '',
}

/**
 * Supplies the site Outlet context that PublicShell normally provides, so
 * consumer pages (which call usePublicSite via PublicLayout) can be rendered in
 * isolation. Use as the element of a parent <Route> wrapping the page route.
 */
export function PublicSiteTestProvider() {
  return <Outlet context={TEST_SITE} />
}
