import { useOutletContext } from 'react-router-dom'
import type { FooterConfig } from '@/shared/lib/footer-config'
import type { HeaderConfig } from '@/shared/lib/header-config'
import type { RecordPageConfig } from '@/shared/lib/record-page-config'
import type { PageLayout } from '@/shared/lib/layout-config'
import type { ThemeLogo } from '@/shared/lib/theme-customization'

export interface PublicSiteNavItem {
  id: number
  url: string
  label: string
  /** Named-menu membership (#347); drives the header-region menu widget (#756). */
  menuId: number | null
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
  /** Optional per-theme logo override (`images.logo`, URLs resolved server-side). #372 */
  themeLogo?: ThemeLogo | undefined
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
  /** Footer content (social icons / legal links / powered-by) from `footer_config` (#766). */
  footerConfig: FooterConfig
  /** Site-wide defaults for the record page's comments / related blocks (`record_page_config`, #775). */
  recordPageConfig: RecordPageConfig
  /** Home masthead — a JSON blocks document (one hero block); '' / '[]' → auto stats-hero. */
  homeHero: string
  /** Pinned front-page permalink path (`front_page` resolved server-side); '' → latest-feed home (#701). */
  frontPagePath: string
}

export function usePublicSite(): PublicSite {
  return useOutletContext<PublicSite>()
}
