import { Link } from 'react-router-dom'
import type { EntityType } from '@/entities/entity-type'
import { useTranslation } from '@/shared/i18n'
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
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.entityTypes.existingList.loading')}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.entityTypes.existingList.error')}</Text>
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
        title={t('admin.entityTypes.existingList.empty.title')}
        description={t('admin.entityTypes.existingList.empty.description')}
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
                  {t('admin.entityTypes.actions.fields')}
                </Button>
              </Link>
            ) : null}
            <Link to={`/entity-types/${String(item.id)}/entities`}>
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
        </li>
      ))}
    </ul>
  )
}
