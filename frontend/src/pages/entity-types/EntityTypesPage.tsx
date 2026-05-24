import { EntityTypeListView, useEntityTypeListPage } from '@/features/list-entity-types'
import { Stack, Text } from '@/shared/ui'

export function EntityTypesPage() {
  const { items, isLoading, isError, errorTitle, refetch } = useEntityTypeListPage()

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        Entity types
      </Text>
      <EntityTypeListView
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
