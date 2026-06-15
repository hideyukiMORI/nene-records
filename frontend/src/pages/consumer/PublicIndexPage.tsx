import { PublicHomeView } from '@/features/public-home'
import { usePublicSite } from './public-site-context'

/**
 * Public home (`/`) — the magazine-style site face: hero, latest cross-type
 * feed, and entity-type entrances. It renders its own self-contained shell
 * (header / footer / theme toggle) rather than the generic PublicLayout, so the
 * homepage chrome and the light/dark theme controller stay scoped to the top
 * page. Site-wide data comes from PublicShell's outlet context.
 */
export function PublicIndexPage() {
  const site = usePublicSite()

  return (
    <PublicHomeView
      siteName={site.siteName}
      tagline={site.tagline}
      metaDescription={site.metaDescription}
      footerMarkdown={site.footerMarkdown}
    />
  )
}
