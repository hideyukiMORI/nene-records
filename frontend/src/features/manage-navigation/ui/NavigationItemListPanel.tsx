import type { NavigationItem } from '@/entities/navigation-item'
import { useTranslation } from '@/shared/i18n'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

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
    return <Text muted>{t('admin.navigation.loading')}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
        <Button variant="secondary" onClick={onRetry}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
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
        <li
          key={String(item.id)}
          className="flex items-center justify-between gap-inline-md rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
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
        </li>
      ))}
    </ul>
  )
}
