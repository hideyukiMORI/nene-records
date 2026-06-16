import { useParams, useSearchParams } from 'react-router-dom'
import {
  parsePublicBrowseOffset,
  PUBLIC_BROWSE_PAGE_SIZE,
} from '@/features/public-browse-entity-records/lib/public-browse-pagination'
import {
  PublicRecordListView,
  usePublicBrowseEntityRecordsPage,
} from '@/features/public-browse-entity-records'
import { PublicSiteShell } from './PublicSiteShell'
import { usePublicSite } from './public-site-context'

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

  const goToOffset = (nextOffset: number) => {
    const params = new URLSearchParams(searchParams)
    if (nextOffset <= 0) {
      params.delete('offset')
    } else {
      params.set('offset', String(nextOffset))
    }
    setSearchParams(params)
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
