import type { Tag } from '@/entities/tag'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

export interface TagListPanelProps {
  items: Tag[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  onRetry: () => void
  onEdit: (tag: Tag) => void
  onDelete: (tag: Tag) => void
}

export function TagListPanel({
  items,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  onRetry,
  onEdit,
  onDelete,
}: TagListPanelProps) {
  if (isLoading) {
    return <Text muted>Loading tags…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load tags</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={onRetry}>
          Retry
        </Button>
      </Stack>
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState title="No tags yet" description="Create your first tag using the form above." />
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
          </div>
        </li>
      ))}
    </ul>
  )
}
