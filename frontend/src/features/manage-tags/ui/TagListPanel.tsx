import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, ErrorState, LoadingState, Stack, Text } from '@/shared/ui'

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
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.tags.list.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.tags.list.error')}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title={t('admin.tags.list.empty.title')}
        description={t('admin.tags.list.empty.description')}
      />
    )
  }

  return (
    <ul className="flex flex-col gap-stack-sm">
      {items.map((item) => (
        <Card
          as="li"
          key={String(item.id)}
          padding="row"
          className="flex items-center justify-between gap-inline-md"
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
              {t('common.actions.edit')}
            </Button>
            <Button
              variant="danger"
              size="sm"
              disabled={isDeleting}
              onClick={() => {
                onDelete(item)
              }}
            >
              {t('common.actions.delete')}
            </Button>
          </div>
        </Card>
      ))}
    </ul>
  )
}
