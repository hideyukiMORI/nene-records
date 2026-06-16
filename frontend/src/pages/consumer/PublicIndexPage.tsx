import { PublicHomeBody, PublicHomeHero, usePublicHomePage } from '@/features/public-home'
import { PublicSiteShell } from './PublicSiteShell'
import { usePublicSite } from './public-site-context'

/**
 * Public home (`/`) — the magazine-style site face: hero, latest cross-type
 * feed, and entity-type entrances, rendered inside the shared PublicSiteShell
 * (header / footer / drawer / light-dark-auto theme). Site-wide data comes from
 * PublicShell's outlet context; the feed/types come from usePublicHomePage.
 */
export function PublicIndexPage() {
  const site = usePublicSite()
  const {
    featured,
    rest,
    types,
    totalPublished,
    typeCount,
    isLoading,
    isError,
    errorTitle,
    refetch,
  } = usePublicHomePage()

  const browseHref = types[0]?.href ?? '/search'

  return (
    <PublicSiteShell
      site={site}
      isHome
      hero={
        <PublicHomeHero
          siteName={site.siteName}
          metaDescription={site.metaDescription}
          totalPublished={totalPublished}
          typeCount={typeCount}
          browseHref={browseHref}
        />
      }
    >
      <PublicHomeBody
        featured={featured}
        rest={rest}
        types={types}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        onRetry={() => {
          void refetch()
        }}
      />
    </PublicSiteShell>
  )
}
