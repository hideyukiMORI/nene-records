import { useTranslation } from '@/shared/i18n'
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
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.entityTypeList.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.entityTypeList.error')}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title={t('admin.entityTypeList.empty.title')}
        description={t('admin.entityTypeList.empty.description')}
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
