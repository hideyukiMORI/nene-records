import { useParams } from 'react-router-dom'
import { PublicDateArchiveView, usePublicDateArchivePage } from '@/features/public-date-archive'
import { useTranslation } from '@/shared/i18n'
import { PublicSiteShell } from './PublicSiteShell'
import { usePublicSite } from './public-site-context'

export function PublicDateArchivePage() {
  const site = usePublicSite()
  const { t } = useTranslation()
  const params = useParams()

  const year = Number(params.year ?? '')
  const month = Number(params.month ?? '')
  const dayParam = params.day
  const day = dayParam === undefined ? null : Number(dayParam)

  const archive = usePublicDateArchivePage(
    Number.isNaN(year) ? 0 : year,
    Number.isNaN(month) ? 0 : month,
    day !== null && Number.isNaN(day) ? 0 : day,
  )

  const title = archive.isDay
    ? t('public.dateArchive.dayTitle', {
        year: String(year),
        month: String(month),
        day: String(day),
      })
    : t('public.dateArchive.monthTitle', { year: String(year), month: String(month) })

  return (
    <PublicSiteShell site={site}>
      <PublicDateArchiveView
        title={title}
        valid={archive.valid}
        groups={archive.groups}
        total={archive.total}
        isLoading={archive.isLoading}
        isError={archive.isError}
        errorTitle={archive.errorTitle}
        onRetry={() => {
          void archive.refetch()
        }}
      />
    </PublicSiteShell>
  )
}
