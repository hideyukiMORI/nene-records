import { Link } from 'react-router-dom'
import type { Entity, EntityStatus } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'

const STATUS_BADGE_CLASS: Record<EntityStatus, string> = {
  draft:
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800',
  published:
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800',
  archived:
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600',
}

export interface EntityListPanelProps {
  entityTypeId: number
  items: Entity[]
  recordLabels: Record<string, string>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  isFilterActive: boolean
  onRetry: () => void
  onDelete: (entity: Entity) => void
}

export function EntityListPanel({
  entityTypeId,
  items,
  recordLabels,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  isFilterActive,
  onRetry,
  onDelete,
}: EntityListPanelProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.entityRecords.list.loading')}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.entityRecords.list.error')}</Text>
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
        title={
          isFilterActive
            ? t('admin.entityRecords.list.emptyFiltered.title')
            : t('admin.entityRecords.list.empty.title')
        }
        description={
          isFilterActive
            ? t('admin.entityRecords.list.emptyFiltered.description')
            : t('admin.entityRecords.list.empty.description')
        }
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
            <div className="flex items-center gap-inline-sm">
              <Text as="span" variant="heading-sm">
                {recordLabels[String(item.id)] ?? t('admin.entityRecord.id', { id: item.id })}
              </Text>
              <span className={STATUS_BADGE_CLASS[item.status]}>
                {t(`admin.entityStatus.status.${item.status}`)}
              </span>
            </div>
            <Text as="span" muted>
              #{String(item.id)}
            </Text>
          </Stack>
          <div className="flex items-center gap-inline-sm">
            <Link to={`/entity-types/${String(entityTypeId)}/entities/${String(item.id)}`}>
              <Button variant="secondary" size="sm">
                {t('common.actions.edit')}
              </Button>
            </Link>
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
