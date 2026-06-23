import { Link } from 'react-router-dom'
import {
  PublicEntityResultGroup,
  type PublicEntityTypeGroup,
} from '@/features/public-entity-results'
import { useTranslation } from '@/shared/i18n'
import { IconArrowLeft, IconInbox } from '@/shared/ui/icons/magazine-icons'

export interface PublicTagArchiveViewProps {
  tagName: string
  groups: PublicEntityTypeGroup[]
  total: number
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicTagArchiveView({
  tagName,
  groups,
  total,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: PublicTagArchiveViewProps) {
  const { t } = useTranslation()

  return (
    <div className="pagehead">
      <Link className="backlink" to="/">
        <IconArrowLeft size={16} /> {t('public.nav.allRecords')}
      </Link>
      <h1 className="pagehead__title">#{tagName}</h1>
      {!isLoading && !isError && total > 0 ? (
        <p className="pagehead__sub">
          {t(total === 1 ? 'public.tagArchive.subCount.one' : 'public.tagArchive.subCount.other', {
            count: total,
            tag: tagName,
          })}
        </p>
      ) : null}

      {isLoading ? (
        <p className="searchhint">{t('public.tagArchive.loading')}</p>
      ) : isError ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <h3 className="empty__title">{t('public.tagArchive.error.title')}</h3>
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
          <h3 className="empty__title">{t('public.tagArchive.empty.title', { tag: tagName })}</h3>
          <p className="empty__text">{t('public.tagArchive.empty.description')}</p>
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
