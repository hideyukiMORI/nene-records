import { useParams } from 'react-router-dom'
import {
  PublicRecordListView,
  usePublicBrowseEntityRecordsPage,
} from '@/features/public-browse-entity-records'
import { Stack, Text } from '@/shared/ui'

export function PublicBrowsePage() {
  const { entityTypeSlug = '' } = useParams()
  const { entityType, items, total, isLoading, isError, isUnknownType, errorTitle, refetch } =
    usePublicBrowseEntityRecordsPage(entityTypeSlug)

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {entityType?.name ?? entityTypeSlug}
      </Text>
      <PublicRecordListView
        entityTypeSlug={entityTypeSlug}
        entityTypeName={entityType?.name ?? null}
        items={items}
        total={total}
        isLoading={isLoading}
        isError={isError}
        isUnknownType={isUnknownType}
        errorTitle={errorTitle}
        onRetry={() => {
          void refetch()
        }}
      />
    </Stack>
  )
}
