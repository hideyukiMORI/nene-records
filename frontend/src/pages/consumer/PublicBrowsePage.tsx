import { Link, useParams, useSearchParams } from 'react-router-dom'
import {
  parsePublicBrowseOffset,
  PUBLIC_BROWSE_PAGE_SIZE,
} from '@/features/public-browse-entity-records/lib/public-browse-pagination'
import {
  PublicRecordListView,
  usePublicBrowseEntityRecordsPage,
} from '@/features/public-browse-entity-records'
import { Button, Stack, Text } from '@/shared/ui'
import { PublicLayout } from './PublicLayout'
import { usePublicSite } from './public-site-context'

export function PublicBrowsePage() {
  const site = usePublicSite()
  const { entityTypeSlug = '' } = useParams()
  const [searchParams, setSearchParams] = useSearchParams()
  const offset = parsePublicBrowseOffset(searchParams.get('offset'))

  const {
    entityType,
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
    <PublicLayout variant="standard" site={site}>
      <Stack gap="md">
        <Stack gap="sm">
          <Link to="/">
            <Button variant="secondary" size="sm">
              All types
            </Button>
          </Link>
          <Text as="h1" variant="heading-md">
            {entityType?.name ?? entityTypeSlug}
          </Text>
        </Stack>
        <PublicRecordListView
          entityTypeSlug={entityTypeSlug}
          entityTypeName={entityType?.name ?? null}
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
      </Stack>
    </PublicLayout>
  )
}
