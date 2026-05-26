import { Link } from 'react-router-dom'
import { useDashboardSummary } from '@/entities/dashboard'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function HomePage() {
  const { t } = useTranslation()
  const { data, isLoading, isError } = useDashboardSummary()

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {t('admin.home.title')}
      </Text>

      {isLoading && <Text muted>{t('admin.home.dashboard.loading')}</Text>}

      {isError && <Text muted>{t('admin.home.dashboard.error')}</Text>}

      {data && (
        <Stack gap="md">
          {/* Access count cards */}
          <div className="grid grid-cols-2 gap-4">
            <div className="rounded border border-border bg-surface-raised p-4">
              <Text muted>{t('admin.home.dashboard.todayAccess')}</Text>
              <Text as="p" variant="heading-md">
                {data.todayAccessCount.toLocaleString()}
              </Text>
            </div>
            <div className="rounded border border-border bg-surface-raised p-4">
              <Text muted>{t('admin.home.dashboard.monthAccess')}</Text>
              <Text as="p" variant="heading-md">
                {data.thisMonthAccessCount.toLocaleString()}
              </Text>
            </div>
          </div>

          {/* Entity type summary */}
          {data.entityTypeSummary.length > 0 && (
            <div>
              <Text as="h2" variant="heading-sm">
                {t('admin.home.dashboard.entityTypeSummary')}
              </Text>
              <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                {data.entityTypeSummary.map((summary) => (
                  <div
                    key={summary.entityTypeId}
                    className="rounded border border-border bg-surface-raised p-3"
                  >
                    <Text as="p">{summary.entityTypeName}</Text>
                    <div className="mt-1 flex gap-4">
                      <Text muted>
                        {t('admin.home.dashboard.published')}: {summary.publishedCount}
                      </Text>
                      <Text muted>
                        {t('admin.home.dashboard.draft')}: {summary.draftCount}
                      </Text>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Recent published */}
          <div>
            <Text as="h2" variant="heading-sm">
              {t('admin.home.dashboard.recentPublished')}
            </Text>
            {data.recentPublished.length === 0 ? (
              <Text muted>{t('admin.home.dashboard.noRecentPublished')}</Text>
            ) : (
              <ul className="mt-2 space-y-1">
                {data.recentPublished.map((entity) => (
                  <li key={entity.id}>
                    <Link
                      to={`/entity-types/${entity.entityTypeSlug}/entities/${String(entity.id)}`}
                      className="text-body text-accent hover:text-accent-hover"
                    >
                      {entity.entityTypeName} / {entity.slug ?? String(entity.id)}
                    </Link>
                    {entity.publishedAt && (
                      <Text as="span" muted>
                        {' '}
                        — {new Date(entity.publishedAt).toLocaleDateString()}
                      </Text>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </div>
        </Stack>
      )}

      <Link to="/view" className="text-body font-medium text-accent hover:text-accent-hover">
        {t('admin.home.openPublicSite')}
      </Link>
    </Stack>
  )
}
