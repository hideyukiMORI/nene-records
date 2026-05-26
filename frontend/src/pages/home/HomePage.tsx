import { Link } from 'react-router-dom'
import { useDashboardSummary } from '@/entities/dashboard'
import { usePinnedEntityTypes } from '@/entities/entity-type'
import { currentUserHasCapability } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'
import { IconFileText, IconLink } from '@/shared/ui/icons/Icons'

export function HomePage() {
  const { t } = useTranslation()
  const { data, isLoading, isError } = useDashboardSummary()
  const pinnedQuery = usePinnedEntityTypes()
  const pinnedTypes = pinnedQuery.data ?? []
  const canManageSettings = currentUserHasCapability('manage_settings')

  // Show "Getting started" when no published content yet — heuristic for fresh installs
  const hasNoContent =
    data !== undefined &&
    data.entityTypeSummary.every((s) => s.publishedCount === 0 && s.draftCount === 0)
  const showGettingStarted = hasNoContent || data === undefined

  return (
    <Stack gap="lg">
      <Text as="h1" variant="heading-md">
        {t('admin.home.title')}
      </Text>

      {/* ── Getting started ── */}
      {showGettingStarted && !isLoading && (pinnedTypes.length > 0 || canManageSettings) ? (
        <section className="rounded-lg border border-accent/30 bg-accent/5 p-5">
          <Text as="h2" variant="heading-sm">
            {t('admin.home.gettingStarted')}
          </Text>
          <Text muted>{t('admin.home.gettingStarted.description')}</Text>
          <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            {pinnedTypes.length > 0 ? (
              <Link
                to={`/admin/${pinnedTypes[0].slug}`}
                className="group flex items-start gap-3 rounded-md border border-border bg-surface-raised p-4 transition-colors hover:border-accent"
              >
                <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent">
                  <IconFileText size={16} />
                </span>
                <span>
                  <span className="block font-medium text-text-primary group-hover:text-accent">
                    {t('admin.home.gettingStarted.content')}
                  </span>
                  <span className="mt-0.5 block text-xs text-text-muted">
                    {t('admin.home.gettingStarted.content.description')}
                  </span>
                </span>
              </Link>
            ) : null}
            {canManageSettings ? (
              <Link
                to="/admin/navigation"
                className="group flex items-start gap-3 rounded-md border border-border bg-surface-raised p-4 transition-colors hover:border-accent"
              >
                <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent">
                  <IconLink size={16} />
                </span>
                <span>
                  <span className="block font-medium text-text-primary group-hover:text-accent">
                    {t('admin.home.gettingStarted.menus')}
                  </span>
                  <span className="mt-0.5 block text-xs text-text-muted">
                    {t('admin.home.gettingStarted.menus.description')}
                  </span>
                </span>
              </Link>
            ) : null}
          </div>
        </section>
      ) : null}

      {/* ── Quick access ── */}
      <section>
        <Text as="h2" variant="heading-sm">
          {t('admin.home.quickAccess')}
        </Text>
        {pinnedTypes.length === 0 && !pinnedQuery.isLoading ? (
          <Text muted>{t('admin.home.quickAccess.empty')}</Text>
        ) : (
          <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {pinnedTypes.map((entityType) => (
              <Link
                key={entityType.id}
                to={`/admin/${entityType.slug}`}
                className="group flex items-center gap-3 rounded-lg border border-border bg-surface-raised p-4 shadow-sm transition-colors hover:border-accent hover:bg-surface"
              >
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent transition-colors group-hover:bg-accent/20">
                  <IconFileText size={18} />
                </span>
                <span className="min-w-0 flex-1">
                  <span className="block truncate font-medium text-text-primary">
                    {entityType.name}
                  </span>
                  <span className="block text-xs text-text-muted">
                    {t('admin.home.quickAccess.manage')}
                  </span>
                </span>
              </Link>
            ))}
          </div>
        )}
      </section>

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
                      to={`/admin/entity-types/${entity.entityTypeSlug}/entities/${String(entity.id)}`}
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

      <Link to="/" className="text-body font-medium text-accent hover:text-accent-hover">
        {t('admin.home.openPublicSite')}
      </Link>
    </Stack>
  )
}
