import { useMemo } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAccessStats, useDashboardSummary } from '@/entities/dashboard'
import { getLocalizedEntityTypeName, usePinnedEntityTypes } from '@/entities/entity-type'
import { currentUserHasCapability } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, PageHeader, Stack, Text } from '@/shared/ui'
import { IconChevronRight, IconFileText, IconGlobe, IconLayers } from '@/shared/ui/icons/Icons'

interface StatCardProps {
  icon: React.ReactNode
  label: string
  value: string
  delta?: string
  chart?: React.ReactNode
}

function StatCard({ icon, label, value, delta, chart }: StatCardProps) {
  return (
    <Card padding="none" className="p-4">
      <div className="flex items-center gap-2 text-xs text-text-muted">
        <span className="opacity-70">{icon}</span>
        <span>{label}</span>
      </div>
      <p className="mt-2 font-display text-heading-md leading-heading font-semibold tracking-tight tabular-nums text-text-primary">
        {value}
      </p>
      {chart ? chart : delta ? <p className="mt-1 text-xs text-text-muted">{delta}</p> : null}
    </Card>
  )
}

/** Console redesign §05 — mini bar sparkline (`.rd-spark`) of daily access counts. */
function Sparkline({ values, label }: { values: number[]; label: string }) {
  const max = Math.max(1, ...values)
  return (
    <div className="sparkline" role="img" aria-label={label}>
      {values.map((value, i) => (
        <span
          key={i}
          className="sparkline__bar"
          style={{ height: `${String(Math.round((value / max) * 100))}%` }}
        />
      ))}
    </div>
  )
}

const SPARK_DAYS = 8

function toIsoDate(date: Date): string {
  const year = String(date.getFullYear())
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

export function HomePage() {
  const { t, locale } = useTranslation()
  const navigate = useNavigate()
  const { data, isLoading, isError } = useDashboardSummary()
  const pinnedQuery = usePinnedEntityTypes()
  const pinnedTypes = pinnedQuery.data ?? []
  const canManageEntities = currentUserHasCapability('edit_content')

  // Daily access counts for the sparkline — last SPARK_DAYS days ending today.
  const range = useMemo(() => {
    const today = new Date()
    const fromDate = new Date(today)
    fromDate.setDate(today.getDate() - (SPARK_DAYS - 1))
    const days: string[] = []
    for (let i = SPARK_DAYS - 1; i >= 0; i--) {
      const d = new Date(today)
      d.setDate(today.getDate() - i)
      days.push(toIsoDate(d))
    }
    return { from: toIsoDate(fromDate), to: toIsoDate(today), days }
  }, [])
  const { data: accessStats } = useAccessStats(range.from, range.to)
  const sparkValues = useMemo(() => {
    const byDate = new Map(accessStats?.items.map((item) => [item.date, item.requestCount]))
    return range.days.map((day) => byDate.get(day) ?? 0)
  }, [accessStats, range.days])

  const summary = data?.entityTypeSummary ?? []
  const totalPublished = summary.reduce((sum, s) => sum + s.publishedCount, 0)
  const totalDrafts = summary.reduce((sum, s) => sum + s.draftCount, 0)

  // "New record" creates within a content type; target the first one available.
  const newRecordSlug = pinnedTypes[0]?.slug ?? summary[0]?.entityTypeSlug

  return (
    <Stack gap="lg">
      {/* ── Page head ── */}
      <PageHeader
        eyebrow={t('admin.home.eyebrow')}
        title={t('admin.home.title')}
        description={t('admin.home.subtitle')}
        actions={
          canManageEntities && newRecordSlug ? (
            <Button
              variant="primary"
              leftIcon={<span aria-hidden="true">+</span>}
              onClick={() => {
                void navigate(`/admin/${newRecordSlug}`)
              }}
            >
              {t('admin.home.newRecord')}
            </Button>
          ) : undefined
        }
      />

      {isLoading ? <Text muted>{t('admin.home.dashboard.loading')}</Text> : null}
      {isError ? <Text muted>{t('admin.home.dashboard.error')}</Text> : null}

      {data ? (
        <>
          {/* ── Stat cards ── */}
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <StatCard
              icon={<IconGlobe size={14} />}
              label={t('admin.home.dashboard.todayAccess')}
              value={data.todayAccessCount.toLocaleString()}
              chart={
                <Sparkline values={sparkValues} label={t('admin.home.dashboard.accessTrend')} />
              }
            />
            <StatCard
              icon={<IconGlobe size={14} />}
              label={t('admin.home.dashboard.monthAccess')}
              value={data.thisMonthAccessCount.toLocaleString()}
            />
            <StatCard
              icon={<IconFileText size={14} />}
              label={t('admin.home.dashboard.published')}
              value={totalPublished.toLocaleString()}
              delta={`${totalDrafts.toLocaleString()} ${t('admin.home.dashboard.draft').toLowerCase()}`}
            />
            <StatCard
              icon={<IconLayers size={14} />}
              label={t('admin.home.dashboard.entityTypeSummary')}
              value={summary.length.toLocaleString()}
            />
          </div>
        </>
      ) : null}

      {/* ── Quick access ── */}
      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('admin.home.quickAccess')}
        </Text>
        {pinnedTypes.length === 0 && !pinnedQuery.isLoading ? (
          <Text muted>{t('admin.home.quickAccess.empty')}</Text>
        ) : (
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
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
                    {getLocalizedEntityTypeName(entityType, locale)}
                  </span>
                  <span className="block text-xs text-text-muted">
                    {t('admin.home.quickAccess.manage')}
                  </span>
                </span>
                <span className="shrink-0 text-text-muted">
                  <IconChevronRight size={16} />
                </span>
              </Link>
            ))}
          </div>
        )}
      </Stack>

      {/* ── Recently published ── */}
      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('admin.home.dashboard.recentPublished')}
        </Text>
        {data && data.recentPublished.length === 0 ? (
          <Text muted>{t('admin.home.dashboard.noRecentPublished')}</Text>
        ) : null}
        {data && data.recentPublished.length > 0 ? (
          <Card padding="none" className="divide-y divide-border overflow-hidden">
            {data.recentPublished.map((entity, i) => (
              <Link
                key={entity.id}
                to={`/admin/entity-types/${entity.entityTypeSlug}/entities/${String(entity.id)}`}
                className="group flex items-center gap-4 px-4 py-3 transition-colors hover:bg-surface"
              >
                <span className="w-6 shrink-0 font-mono text-xs text-text-muted">#{i + 1}</span>
                <span className="min-w-0 flex-1">
                  <span className="block truncate font-medium text-text-primary group-hover:text-accent">
                    {entity.slug ?? String(entity.id)}
                  </span>
                  <span className="block text-xs text-text-muted">
                    {entity.entityTypeName}
                    {entity.publishedAt
                      ? ` · ${new Date(entity.publishedAt).toLocaleDateString()}`
                      : ''}
                  </span>
                </span>
                <span className="shrink-0 text-text-muted">
                  <IconChevronRight size={16} />
                </span>
              </Link>
            ))}
          </Card>
        ) : null}
      </Stack>

      <Link to="/" className="text-body font-medium text-accent hover:text-accent-hover">
        {t('admin.home.openPublicSite')}
      </Link>
    </Stack>
  )
}
