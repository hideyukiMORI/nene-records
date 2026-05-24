import { PublicEntityTypeListView, usePublicBrowseIndexPage } from '@/features/public-browse-index'
import { Stack, Text } from '@/shared/ui'

export function PublicIndexPage() {
  const { items, isLoading, isError, errorTitle, refetch } = usePublicBrowseIndexPage()

  return (
    <Stack gap="md">
      <Stack gap="sm">
        <Text as="h1" variant="heading-md">
          Browse
        </Text>
        <Text muted>Public records grouped by entity type.</Text>
      </Stack>
      <PublicEntityTypeListView
        items={items}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        onRetry={() => {
          void refetch()
        }}
      />
    </Stack>
  )
}
