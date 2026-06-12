import { PublicEntityTypeListView, usePublicBrowseIndexPage } from '@/features/public-browse-index'
import { Stack, Text } from '@/shared/ui'
import { PublicLayout } from './PublicLayout'
import { usePublicSite } from './public-site-context'

export function PublicIndexPage() {
  const site = usePublicSite()
  const { items, isLoading, isError, errorTitle, refetch } = usePublicBrowseIndexPage()

  return (
    <PublicLayout variant="standard" site={site}>
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
    </PublicLayout>
  )
}
