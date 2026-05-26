import { Link } from 'react-router-dom'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'
import type { PublicRecordListItem } from '../hooks/use-public-browse-entity-records-page'

export interface PublicRecordListViewProps {
  entityTypeSlug: string
  entityTypeName: string | null
  items: PublicRecordListItem[]
  total: number
  offset: number
  pageSize: number
  hasPreviousPage: boolean
  hasNextPage: boolean
  onPreviousPage: () => void
  onNextPage: () => void
  isLoading: boolean
  isError: boolean
  isUnknownType: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicRecordListView({
  entityTypeSlug,
  entityTypeName,
  items,
  total,
  offset,
  pageSize,
  hasPreviousPage,
  hasNextPage,
  onPreviousPage,
  onNextPage,
  isLoading,
  isError,
  isUnknownType,
  errorTitle,
  onRetry,
}: PublicRecordListViewProps) {
  if (isLoading) {
    return <Text muted>Loading…</Text>
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

  if (isUnknownType) {
    return (
      <EmptyState
        title="Entity type not found"
        description={`No public content for "${entityTypeSlug}".`}
      />
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title="No records yet"
        description={`${entityTypeName ?? entityTypeSlug} has no published records.`}
      />
    )
  }

  return (
    <Stack gap="md">
      <Text as="p" muted>
        {total} record{total === 1 ? '' : 's'}
        {total > pageSize
          ? ` · showing ${String(offset + 1)}–${String(Math.min(offset + items.length, total))}`
          : ''}
      </Text>
      <ul className="flex flex-col gap-stack-sm">
        {items.map((item) => (
          <li
            key={String(item.id)}
            className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
          >
            <Link
              to={`/${entityTypeSlug}/${String(item.id)}`}
              className="font-sans text-heading-sm font-semibold text-text-primary hover:text-accent"
            >
              {item.label}
            </Link>
          </li>
        ))}
      </ul>
      {(hasPreviousPage || hasNextPage) && (
        <Stack direction="horizontal" gap="sm">
          <Button
            variant="secondary"
            size="sm"
            disabled={!hasPreviousPage}
            onClick={onPreviousPage}
          >
            Previous
          </Button>
          <Button variant="secondary" size="sm" disabled={!hasNextPage} onClick={onNextPage}>
            Next
          </Button>
        </Stack>
      )}
    </Stack>
  )
}
