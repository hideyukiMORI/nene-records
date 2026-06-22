import { PublicHomeBody, PublicHomeHero, usePublicHomePage } from '@/features/public-home'
import { parseBlocksDocument } from '@/shared/lib/blocks-document'
import { activeSideRegions } from '@/shared/lib/layout-config'
import { BlocksRenderer } from '@/shared/ui/blocks'
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

  // Top-page columns come from the admin layout config (layout_config.home):
  // a side region renders when the config enables it AND it has widgets.
  const homeSides = activeSideRegions(site.homeLayout)

  // A configured `home_hero` block replaces the auto stats-hero (fallback when empty).
  const hasHomeHero = parseBlocksDocument(site.homeHero).length > 0
  const hero = hasHomeHero ? (
    <div className="wrap">
      <BlocksRenderer documentJson={site.homeHero} />
    </div>
  ) : (
    <PublicHomeHero
      siteName={site.siteName}
      metaDescription={site.metaDescription}
      totalPublished={totalPublished}
      typeCount={typeCount}
      browseHref={browseHref}
    />
  )

  return (
    <PublicSiteShell
      site={site}
      isHome
      withSidebar={homeSides.includes('sidebar')}
      withAside={homeSides.includes('aside')}
      hero={hero}
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
