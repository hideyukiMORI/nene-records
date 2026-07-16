import { useParams, useSearchParams } from 'react-router-dom'
import {
  parsePublicBrowseOffset,
  PUBLIC_BROWSE_PAGE_SIZE,
} from '@/features/public-browse-entity-records/lib/public-browse-pagination'
import {
  PublicRecordListView,
  usePublicBrowseEntityRecordsPage,
} from '@/features/public-browse-entity-records'
import { PublicRecordByPermalink } from './PublicRecordDetailPage'
import { PublicSiteShell } from './PublicSiteShell'
import { usePublicSite } from './public-site-context'
import { usePublicDocumentTitle } from './use-public-document-title'

export function PublicBrowsePage() {
  const site = usePublicSite()
  const { entityTypeSlug = '' } = useParams()
  const [searchParams, setSearchParams] = useSearchParams()
  const offset = parsePublicBrowseOffset(searchParams.get('offset'))

  const {
    entityType,
    entityTypes,
    items,
    total,
    pageSize,
    hasPreviousPage,
    hasNextPage,
    isLoading,
    isError,
    isUnknownType,
    errorTitle,
    refetch,
  } = usePublicBrowseEntityRecordsPage(entityTypeSlug, offset)

  // Tab title parity with the SSR type-archive `<title>` (#909), which uses the
  // type's display name as the page title. When the slug turns out to be a
  // custom permalink (isUnknownType), the delegate page owns the title instead.
  usePublicDocumentTitle(isUnknownType ? undefined : (entityType?.name ?? null), site.siteName)

  const goToOffset = (nextOffset: number) => {
    const params = new URLSearchParams(searchParams)
    if (nextOffset <= 0) {
      params.delete('offset')
    } else {
      params.set('offset', String(nextOffset))
    }
    // Cross-fade pagination like every other public navigation (#921).
    setSearchParams(params, { viewTransition: true })
  }

  // A single-segment URL whose slug isn't a known type may be a top-level custom
  // permalink (e.g. `/about`) → resolve it as one instead of "unknown type" (#656).
  if (isUnknownType) {
    return <PublicRecordByPermalink path={`/${entityTypeSlug}`} />
  }

  return (
    <PublicSiteShell site={site} activeTypeSlug={entityTypeSlug}>
      <PublicRecordListView
        entityTypeSlug={entityTypeSlug}
        entityTypeName={entityType?.name ?? null}
        entityTypes={entityTypes}
        items={items}
        total={total}
        offset={offset}
        pageSize={pageSize}
        hasPreviousPage={hasPreviousPage}
        hasNextPage={hasNextPage}
        onPreviousPage={() => {
          goToOffset(Math.max(0, offset - PUBLIC_BROWSE_PAGE_SIZE))
        }}
        onNextPage={() => {
          goToOffset(offset + PUBLIC_BROWSE_PAGE_SIZE)
        }}
        isLoading={isLoading}
        isError={isError}
        isUnknownType={isUnknownType}
        errorTitle={errorTitle}
        onRetry={() => {
          void refetch()
        }}
      />
    </PublicSiteShell>
  )
}
