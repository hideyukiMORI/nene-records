import type { NavigationItem } from '@/entities/navigation-item'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, ErrorState, LoadingState, Stack, Text } from '@/shared/ui'

export interface NavigationItemListPanelProps {
  items: NavigationItem[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  onRetry: () => void
  onEdit: (item: NavigationItem) => void
  onDelete: (item: NavigationItem) => void
}

export function NavigationItemListPanel({
  items,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  onRetry,
  onEdit,
  onDelete,
}: NavigationItemListPanelProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.navigation.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title={t('admin.navigation.empty')}
        description={t('admin.navigation.empty.description')}
        action={<span className="text-xs text-text-muted">{t('admin.navigation.empty.hint')}</span>}
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
              {item.label}
            </Text>
            <Text as="span" muted>
              {item.url}
            </Text>
            <Text as="span" muted variant="caption">
              {t('admin.navigation.displayOrder')}: {item.displayOrder}
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
