import { Card, EmptyState, ErrorState, LoadingState, Stack, Text } from '@/shared/ui'
import type { EntityTypeListItem } from '../hooks/use-entity-type-list-page'

export interface EntityTypeListViewProps {
  items: EntityTypeListItem[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function EntityTypeListView({
  items,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: EntityTypeListViewProps) {
  if (isLoading) {
    return <LoadingState>Loading entity types…</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title="Could not load entity types"
        message={errorTitle ?? 'Unknown error'}
        onRetry={onRetry}
        retryLabel="Retry"
      />
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title="No entity types yet"
        description="Entity type editor screens will arrive in a follow-up issue."
      />
    )
  }

  return (
    <ul className="flex flex-col gap-stack-sm">
      {items.map((item) => (
        <Card as="li" key={String(item.id)} padding="row">
          <Stack gap="xs">
            <Text as="span" variant="heading-sm">
              {item.name}
            </Text>
            <Text as="span" muted>
              {item.slug}
            </Text>
          </Stack>
        </Card>
      ))}
    </ul>
  )
}
