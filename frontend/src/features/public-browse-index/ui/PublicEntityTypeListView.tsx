import { Link } from 'react-router-dom'
import { Card, EmptyState, ErrorState, LoadingState, Text } from '@/shared/ui'
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
    return <LoadingState>Loading…</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title="Could not load content types"
        message={errorTitle ?? 'Unknown error'}
        onRetry={onRetry}
        retryLabel="Retry"
      />
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
        <Card as="li" key={String(item.id)} padding="row">
          <Link
            to={`/${item.slug}`}
            className="font-sans text-heading-sm font-semibold text-text-primary hover:text-accent"
          >
            {item.name}
          </Link>
          <Text as="p" muted className="mt-stack-xs">
            /{item.slug}
          </Text>
        </Card>
      ))}
    </ul>
  )
}
