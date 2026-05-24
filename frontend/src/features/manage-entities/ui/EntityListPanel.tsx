import { Link } from 'react-router-dom'
import type { Entity, EntityStatus } from '@/entities/entity'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

const STATUS_BADGE_CLASS: Record<EntityStatus, string> = {
  draft:
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800',
  published:
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800',
  archived:
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600',
}

export interface EntityListPanelProps {
  entityTypeId: number
  items: Entity[]
  recordLabels: Record<string, string>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  isFilterActive: boolean
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
  isFilterActive,
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
        title={isFilterActive ? 'No matching records' : 'No records yet'}
        description={
          isFilterActive
            ? 'Try clearing the filters or selecting different criteria.'
            : 'Create your first record using the button above.'
        }
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
            <div className="flex items-center gap-inline-sm">
              <Text as="span" variant="heading-sm">
                {recordLabels[String(item.id)] ?? `Record #${String(item.id)}`}
              </Text>
              <span className={STATUS_BADGE_CLASS[item.status]}>{item.status}</span>
            </div>
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
