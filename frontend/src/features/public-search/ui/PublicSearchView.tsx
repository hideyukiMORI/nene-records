import { useState } from 'react'
import { Link } from 'react-router-dom'
import {
  PublicEntityResultGroup,
  type PublicEntityTypeGroup,
} from '@/features/public-entity-results'
import { useTranslation } from '@/shared/i18n'
import { IconArrowLeft, IconInbox } from '@/shared/ui/icons/magazine-icons'
import { IconSearch } from '@/shared/ui/icons/Icons'

export interface PublicSearchViewProps {
  query: string
  hasQuery: boolean
  groups: PublicEntityTypeGroup[]
  total: number
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onSearch: (q: string) => void
  onRetry: () => void
}

export function PublicSearchView({
  query,
  hasQuery,
  groups,
  total,
  isLoading,
  isError,
  errorTitle,
  onSearch,
  onRetry,
}: PublicSearchViewProps) {
  const { t } = useTranslation()
  const [input, setInput] = useState(query)

  return (
    <div className="pagehead">
      <Link className="backlink" to="/">
        <IconArrowLeft size={16} /> {t('public.nav.allRecords')}
      </Link>
      <h1 className="pagehead__title">{t('public.search.title')}</h1>
      <p className="pagehead__sub">{t('public.search.sub')}</p>

      <form
        className="searchbar"
        role="search"
        onSubmit={(event) => {
          event.preventDefault()
          onSearch(input.trim())
        }}
      >
        <IconSearch size={20} />
        <input
          // eslint-disable-next-line jsx-a11y/no-autofocus
          autoFocus
          type="search"
          value={input}
          autoComplete="off"
          placeholder={t('public.search.inputPlaceholder')}
          aria-label={t('public.search.inputLabel')}
          onChange={(event) => {
            setInput(event.target.value)
          }}
        />
      </form>

      {!hasQuery ? (
        <p className="searchhint">{t('public.search.prompt')}</p>
      ) : isLoading ? (
        <p className="searchhint">{t('public.search.loading')}</p>
      ) : isError ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <h3 className="empty__title">{t('public.search.error.title')}</h3>
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
          <h3 className="empty__title">{t('public.search.empty.title', { query })}</h3>
          <p className="empty__text">{t('public.search.empty.description')}</p>
          <Link className="btn btn--ghost" to="/">
            {t('public.nav.backToLatest')}
          </Link>
        </div>
      ) : (
        <>
          <p className="searchhint">
            {t(total === 1 ? 'public.search.resultCount.one' : 'public.search.resultCount.other', {
              count: total,
              query,
            })}
          </p>
          <div style={{ marginTop: 'var(--space-lg)' }}>
            {groups.map((group) => (
              <PublicEntityResultGroup
                key={String(group.entityType.id)}
                entityType={group.entityType}
                entities={group.entities}
              />
            ))}
          </div>
        </>
      )}
    </div>
  )
}
