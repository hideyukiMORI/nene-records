import { useOutletContext } from 'react-router-dom'
import type { HeaderConfig } from '@/shared/lib/header-config'
import type { PageLayout } from '@/shared/lib/layout-config'

export interface PublicSiteNavItem {
  id: number
  url: string
  label: string
}

/**
 * Site-wide data resolved once by PublicShell and consumed by each page's
 * PublicSiteShell. Kept in a small module so the shell and the pages can share
 * the type without a circular import.
 */
export interface PublicSite {
  siteName: string
  tagline: string
  metaDescription: string
  footerMarkdown: string
  /** Logo image URL (`logo_media_id` resolved server-side); '' → brand mark. */
  logo: string
  /** Footer copyright text; `{year}`/`{site}` tokens substituted at render. */
  copyrightText: string
  /** Top-page column layout (`layout_config.home`) — drives sidebar/aside on home. */
  homeLayout: PageLayout
  navItems: PublicSiteNavItem[]
  /** Admin-selected public-site theme id (`active_theme` setting). */
  activeTheme: string
  /** Customizer overrides for the active theme, as a scoped CSS stylesheet. */
  themeOverrideCss: string
  /** Runtime (data-driven) active theme tokens as a scoped stylesheet; '' for built-in themes. */
  runtimeThemeCss: string
  /** Structural style-flag `data-*` attributes for the active theme. */
  themeFlagAttrs: Record<string, string>
  /** Header content (Top bar + CTA) from the `header_config` setting. */
  headerConfig: HeaderConfig
}

export function usePublicSite(): PublicSite {
  return useOutletContext<PublicSite>()
}
