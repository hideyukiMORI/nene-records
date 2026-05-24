import { Link } from 'react-router-dom'
import type { EntityType } from '@/entities/entity-type'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

export interface EntityTypeListPanelProps {
  items: EntityType[]
  canManageSchema: boolean
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  onRetry: () => void
  onEdit: (entityType: EntityType) => void
  onDelete: (entityType: EntityType) => void
}

export function EntityTypeListPanel({
  items,
  canManageSchema,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  onRetry,
  onEdit,
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
          <div className="flex items-center gap-inline-sm">
            {canManageSchema ? (
              <Link to={`/entity-types/${String(item.id)}/fields`}>
                <Button variant="secondary" size="sm">
                  Fields
                </Button>
              </Link>
            ) : null}
            <Link to={`/entity-types/${String(item.id)}/entities`}>
              <Button variant="secondary" size="sm">
                Records
              </Button>
            </Link>
            {canManageSchema ? (
              <>
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => {
                    onEdit(item)
                  }}
                >
                  Edit
                </Button>
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
              </>
            ) : null}
          </div>
        </li>
      ))}
    </ul>
  )
}
