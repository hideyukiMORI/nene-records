import type { EntityType } from '@/entities/entity-type'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

export interface EntityTypeListPanelProps {
  items: EntityType[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  onRetry: () => void
  onDelete: (entityType: EntityType) => void
}

export function EntityTypeListPanel({
  items,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  onRetry,
  onDelete,
}: EntityTypeListPanelProps) {
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
        description="Create your first entity type using the form above."
      />
    )
  }

  return (
    <ul className="flex flex-col gap-stack-sm">
      {items.map((item) => (
        <li
          key={String(item.id)}
          className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
        >
          <Stack gap="xs">
            <Text as="span" variant="heading-sm">
              {item.name}
            </Text>
            <Text as="span" muted>
              {item.slug}
            </Text>
          </Stack>
          <Button
            variant="danger"
            size="sm"
            disabled={isDeleting}
            onClick={() => {
              onDelete(item)
            }}
          >
            Delete
          </Button>
        </li>
      ))}
    </ul>
  )
}
