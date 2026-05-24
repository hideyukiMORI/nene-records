import { Link } from 'react-router-dom'
import type { Entity } from '@/entities/entity'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

export interface EntityListPanelProps {
  entityTypeId: number
  items: Entity[]
  recordLabels: Record<string, string>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  onRetry: () => void
  onDelete: (entity: Entity) => void
}

export function EntityListPanel({
  entityTypeId,
  items,
  recordLabels,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  onRetry,
  onDelete,
}: EntityListPanelProps) {
  if (isLoading) {
    return <Text muted>Loading records…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load records</Text>
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
        title="No records yet"
        description="Create your first record using the button above."
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
              {recordLabels[String(item.id)] ?? `Record #${String(item.id)}`}
            </Text>
            <Text as="span" muted>
              #{String(item.id)}
            </Text>
          </Stack>
          <div className="flex items-center gap-inline-sm">
            <Link to={`/entity-types/${String(entityTypeId)}/entities/${String(item.id)}`}>
              <Button variant="secondary" size="sm">
                Edit
              </Button>
            </Link>
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
          </div>
        </li>
      ))}
    </ul>
  )
}
