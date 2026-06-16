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
  navItems: PublicSiteNavItem[]
}

export function usePublicSite(): PublicSite {
  return useOutletContext<PublicSite>()
}
