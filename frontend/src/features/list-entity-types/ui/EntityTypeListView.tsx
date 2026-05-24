import { Button, EmptyState, Stack, Text } from '@/shared/ui'
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
    return <Text muted>Loading entity types…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load entity types</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={onRetry}>
          Retry
        </Button>
      </Stack>
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
        <li
          key={String(item.id)}
          className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
        >
          <Stack gap="xs">
            <Text as="span" variant="heading-sm">
              {item.name}
            </Text>
            <Text as="span" muted>
              {item.slug}
            </Text>
          </Stack>
        </li>
      ))}
    </ul>
  )
}
