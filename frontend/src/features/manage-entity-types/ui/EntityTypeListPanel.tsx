import { Link } from 'react-router-dom'
import { getLocalizedEntityTypeName, type EntityType } from '@/entities/entity-type'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, ErrorState, LoadingState, Stack, Text } from '@/shared/ui'
import { IconChevronDown, IconChevronUp } from '@/shared/ui/icons/Icons'

export interface EntityTypeListPanelProps {
  items: EntityType[]
  canManageSchema: boolean
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  isReordering: boolean
  onRetry: () => void
  onEdit: (entityType: EntityType) => void
  onDelete: (entityType: EntityType) => void
  onMove: (entityType: EntityType, direction: 'up' | 'down') => void
}

export function EntityTypeListPanel({
  items,
  canManageSchema,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  isReordering,
  onRetry,
  onEdit,
  onDelete,
  onMove,
}: EntityTypeListPanelProps) {
  const { t, locale } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.entityTypes.existingList.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.entityTypes.existingList.error')}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title={t('admin.entityTypes.existingList.empty.title')}
        description={t('admin.entityTypes.existingList.empty.description')}
      />
    )
  }

  return (
    <ul className="flex flex-col gap-stack-sm">
      {items.map((item, index) => (
        <Card
          as="li"
          key={String(item.id)}
          padding="row"
          className="flex items-center justify-between gap-inline-md"
        >
          <div className="flex min-w-0 items-center gap-inline-sm">
            {canManageSchema ? (
              <div className="flex shrink-0 flex-col">
                <button
                  type="button"
                  aria-label={t('admin.entityTypes.actions.moveUp')}
                  disabled={index === 0 || isReordering}
                  onClick={() => {
                    onMove(item, 'up')
                  }}
                  className="rounded-sm p-0.5 text-text-muted transition-colors hover:text-text-primary disabled:cursor-not-allowed disabled:opacity-30"
                >
                  <IconChevronUp size={14} />
                </button>
                <button
                  type="button"
                  aria-label={t('admin.entityTypes.actions.moveDown')}
                  disabled={index === items.length - 1 || isReordering}
                  onClick={() => {
                    onMove(item, 'down')
                  }}
                  className="rounded-sm p-0.5 text-text-muted transition-colors hover:text-text-primary disabled:cursor-not-allowed disabled:opacity-30"
                >
                  <IconChevronDown size={14} />
                </button>
              </div>
            ) : null}
            <Stack gap="xs">
              <Text as="span" variant="heading-sm">
                {getLocalizedEntityTypeName(item, locale)}
              </Text>
              <Text as="span" muted>
                {item.slug}
              </Text>
            </Stack>
          </div>
          <div className="flex items-center gap-inline-sm">
            {canManageSchema ? (
              <Link to={`/admin/entity-types/${item.slug}/fields`}>
                <Button variant="secondary" size="sm">
                  {t('admin.entityTypes.actions.fields')}
                </Button>
              </Link>
            ) : null}
            <Link to={`/admin/${item.slug}`}>
              <Button variant="secondary" size="sm">
                {t('admin.entityTypes.actions.records')}
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
              </>
            ) : null}
          </div>
        </Card>
      ))}
    </ul>
  )
}
