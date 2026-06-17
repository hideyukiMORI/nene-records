import { useOutletContext } from 'react-router-dom'

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
  navItems: PublicSiteNavItem[]
  /** Admin-selected public-site theme id (`active_theme` setting). */
  activeTheme: string
  /** Customizer overrides for the active theme, as a scoped CSS stylesheet. */
  themeOverrideCss: string
  /** Structural style-flag `data-*` attributes for the active theme. */
  themeFlagAttrs: Record<string, string>
}

export function usePublicSite(): PublicSite {
  return useOutletContext<PublicSite>()
}
