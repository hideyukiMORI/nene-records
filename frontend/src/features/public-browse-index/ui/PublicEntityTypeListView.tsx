import { Link } from 'react-router-dom'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'
import type { PublicEntityTypeListItem } from '../hooks/use-public-browse-index-page'

export interface PublicEntityTypeListViewProps {
  items: PublicEntityTypeListItem[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicEntityTypeListView({
  items,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: PublicEntityTypeListViewProps) {
  if (isLoading) {
    return <Text muted>Loading…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load content types</Text>
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
        title="No content types yet"
        description="Entity types will appear here when they are registered in the API."
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
          <Link
            to={`/view/${item.slug}`}
            className="font-sans text-heading-sm font-semibold text-text-primary hover:text-accent"
          >
            {item.name}
          </Link>
          <Text as="p" muted className="mt-stack-xs">
            /view/{item.slug}
          </Text>
        </li>
      ))}
    </ul>
  )
}
