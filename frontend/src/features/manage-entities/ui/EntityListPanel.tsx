import { Link } from 'react-router-dom'
import type { Entity } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, EmptyState, Stack, StatusBadge, Text } from '@/shared/ui'

function formatDate(iso: string | null, locale: string): string {
  if (iso === null) return ''
  try {
    return new Intl.DateTimeFormat(locale, {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(iso))
  } catch {
    return iso
  }
}

export interface EntityListPanelProps {
  entityTypeSlug: string
  items: Entity[]
  recordLabels: Record<string, string>
  recordBodyMap: Record<string, string>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isDeleting: boolean
  isFilterActive: boolean
  onRetry: () => void
  onDelete: (entity: Entity) => void
}

export function EntityListPanel({
  entityTypeSlug,
  items,
  recordLabels,
  recordBodyMap,
  isLoading,
  isError,
  errorTitle,
  isDeleting,
  isFilterActive,
  onRetry,
  onDelete,
}: EntityListPanelProps) {
  const { t, locale } = useTranslation()

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
      {items.map((item) => {
        const label = recordLabels[String(item.id)] ?? t('admin.entityRecord.id', { id: item.id })
        const body = recordBodyMap[String(item.id)]
        const createdStr = formatDate(item.createdAt, locale)
        const showUpdated = item.updatedAt !== null && item.updatedAt !== item.createdAt

        return (
          <li
            key={String(item.id)}
            className="flex items-start justify-between gap-inline-md rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm"
          >
            {/* 左カラム: ID・タイトル・ステータス・body・日時 */}
            <div className="min-w-0 flex-1">
              {/* 行1: #id + タイトル + ステータスバッジ */}
              <div className="flex flex-wrap items-center gap-x-inline-sm gap-y-0.5">
                <span className="shrink-0 font-sans text-caption text-text-muted">
                  #{String(item.id)}
                </span>
                <Text as="span" variant="heading-sm">
                  {label}
                </Text>
                <StatusBadge status={item.status}>
                  {t(`admin.entityStatus.status.${item.status}`)}
                </StatusBadge>
              </div>

              {/* 行2: body 1行（truncate） */}
              {body !== undefined ? (
                <p className="mt-0.5 truncate font-sans text-caption text-text-muted">{body}</p>
              ) : null}

              {/* 行3: 作成日時 / 更新日時 */}
              {createdStr !== '' ? (
                <div className="mt-1 flex flex-wrap gap-x-inline-md gap-y-0.5">
                  <span className="font-sans text-caption text-text-muted">
                    {t('admin.entityRecords.list.item.createdAt', { date: createdStr })}
                  </span>
                  {showUpdated ? (
                    <span className="font-sans text-caption text-text-muted">
                      {t('admin.entityRecords.list.item.updatedAt', {
                        date: formatDate(item.updatedAt, locale),
                      })}
                    </span>
                  ) : null}
                </div>
              ) : null}
            </div>

            {/* 右カラム: アクションボタン */}
            <div className="flex shrink-0 items-center gap-inline-sm">
              <Link to={`/admin/${entityTypeSlug}/${String(item.id)}`}>
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
        )
      })}
    </ul>
  )
}
