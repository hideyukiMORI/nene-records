import { Link } from 'react-router-dom'
import type { Entity } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, ErrorState, LoadingState, StatusBadge, Text } from '@/shared/ui'

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
    return <LoadingState>{t('admin.entityRecords.list.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.entityRecords.list.error')}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
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
          <Card
            as="li"
            key={String(item.id)}
            padding="row"
            className="flex items-center gap-inline-md"
          >
            {/* #id 列（等幅・固定幅・muted）— 参考 posts.html の .rd-rec__id */}
            <span className="w-7 shrink-0 font-mono text-caption text-text-muted">
              #{String(item.id)}
            </span>

            {/* 中央カラム: タイトル・ステータス・body・日時 */}
            <div className="min-w-0 flex-1">
              {/* 行1: タイトル + ステータスバッジ（タイトルは 1 行 truncate・#849） */}
              <div className="flex items-center gap-x-inline-sm gap-y-0.5">
                <Text as="span" variant="heading-sm" className="min-w-0 truncate">
                  {label}
                </Text>
                <StatusBadge status={item.status} className="shrink-0">
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
          </Card>
        )
      })}
    </ul>
  )
}
