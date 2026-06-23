import { Link } from 'react-router-dom'
import {
  PublicEntityResultGroup,
  type PublicEntityTypeGroup,
} from '@/features/public-entity-results'
import { useTranslation } from '@/shared/i18n'
import { IconArrowLeft, IconInbox } from '@/shared/ui/icons/magazine-icons'

export interface PublicDateArchiveViewProps {
  title: string
  valid: boolean
  groups: PublicEntityTypeGroup[]
  total: number
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicDateArchiveView({
  title,
  valid,
  groups,
  total,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: PublicDateArchiveViewProps) {
  const { t } = useTranslation()

  return (
    <div className="pagehead">
      <Link className="backlink" to="/">
        <IconArrowLeft size={16} /> {t('public.nav.allRecords')}
      </Link>
      <h1 className="pagehead__title">{title}</h1>
      {valid && !isLoading && !isError && total > 0 ? (
        <p className="pagehead__sub">
          {t(
            total === 1 ? 'public.dateArchive.subCount.one' : 'public.dateArchive.subCount.other',
            { count: total },
          )}
        </p>
      ) : null}

      {!valid ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <span className="empty__icon">
            <IconInbox size={26} />
          </span>
          <h3 className="empty__title">{t('public.dateArchive.invalid.title')}</h3>
          <p className="empty__text">{t('public.dateArchive.invalid.description')}</p>
          <Link className="btn btn--ghost" to="/">
            {t('public.nav.backToLatest')}
          </Link>
        </div>
      ) : isLoading ? (
        <p className="searchhint">{t('public.dateArchive.loading')}</p>
      ) : isError ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <h3 className="empty__title">{t('public.dateArchive.error.title')}</h3>
          <p className="empty__text">{errorTitle ?? t('common.error.unknown')}</p>
          <button type="button" className="btn btn--ghost" onClick={onRetry}>
            {t('common.actions.retry')}
          </button>
        </div>
      ) : total === 0 ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <span className="empty__icon">
            <IconInbox size={26} />
          </span>
          <h3 className="empty__title">{t('public.dateArchive.empty.title')}</h3>
          <p className="empty__text">{t('public.dateArchive.empty.description')}</p>
          <Link className="btn btn--ghost" to="/">
            {t('public.nav.backToLatest')}
          </Link>
        </div>
      ) : (
        <div style={{ marginTop: 'var(--space-xl)' }}>
          {groups.map((group) => (
            <PublicEntityResultGroup
              key={String(group.entityType.id)}
              entityType={group.entityType}
              entities={group.entities}
            />
          ))}
        </div>
      )}
    </div>
  )
}
