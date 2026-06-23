import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import {
  IconArrow,
  IconArrowLeft,
  IconGrid,
  IconInbox,
  IconList,
} from '@/shared/ui/icons/magazine-icons'
import type {
  PublicBrowseType,
  PublicRecordListItem,
} from '../hooks/use-public-browse-entity-records-page'

type BrowseView = 'list' | 'grid'
const VIEW_STORAGE_KEY = 'nene_public_browse_view'

function readStoredView(): BrowseView {
  if (typeof window === 'undefined') {
    return 'list'
  }
  return window.localStorage.getItem(VIEW_STORAGE_KEY) === 'grid' ? 'grid' : 'list'
}

export interface PublicRecordListViewProps {
  entityTypeSlug: string
  entityTypeName: string | null
  entityTypes: PublicBrowseType[]
  items: PublicRecordListItem[]
  total: number
  offset: number
  pageSize: number
  hasPreviousPage: boolean
  hasNextPage: boolean
  onPreviousPage: () => void
  onNextPage: () => void
  isLoading: boolean
  isError: boolean
  isUnknownType: boolean
  errorTitle: string | null
  onRetry: () => void
}

function TypeBadge({ slug, name }: { slug: string; name: string }) {
  return (
    <Link className="tbadge" to={`/${slug}`}>
      {name.toLowerCase()}
    </Link>
  )
}

function Eyecatch({ label, className }: { label: string; className: string }) {
  return (
    <div
      className={`eyecatch ${className}`}
      role="img"
      aria-label={`${label} placeholder`}
      data-label={label}
    />
  )
}

function GridCard({
  item,
  typeSlug,
  typeName,
}: {
  item: PublicRecordListItem
  typeSlug: string
  typeName: string
}) {
  return (
    <article className="card">
      <Link to={item.publicUrl}>
        <Eyecatch label="eyecatch · 16:10" className="card__media" />
      </Link>
      <div className="card__metarow">
        <TypeBadge slug={typeSlug} name={typeName} />
        {item.publishedLabel !== '' ? <span className="meta">{item.publishedLabel}</span> : null}
      </div>
      <h3 className="card__title">
        <Link to={item.publicUrl}>{item.label}</Link>
      </h3>
    </article>
  )
}

function ListRow({
  item,
  typeSlug,
  typeName,
}: {
  item: PublicRecordListItem
  typeSlug: string
  typeName: string
}) {
  return (
    <article className="row">
      <Link to={item.publicUrl}>
        <Eyecatch label="eyecatch · 16:10" className="row__media" />
      </Link>
      <div className="row__body">
        <div className="row__metarow">
          <TypeBadge slug={typeSlug} name={typeName} />
          {item.publishedLabel !== '' ? <span className="meta">{item.publishedLabel}</span> : null}
        </div>
        <h3 className="row__title">
          <Link to={item.publicUrl}>{item.label}</Link>
        </h3>
      </div>
    </article>
  )
}

export function PublicRecordListView({
  entityTypeSlug,
  entityTypeName,
  entityTypes,
  items,
  total,
  offset,
  pageSize,
  hasPreviousPage,
  hasNextPage,
  onPreviousPage,
  onNextPage,
  isLoading,
  isError,
  isUnknownType,
  errorTitle,
  onRetry,
}: PublicRecordListViewProps) {
  const { t } = useTranslation()
  const [view, setView] = useState<BrowseView>(readStoredView)
  useEffect(() => {
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(VIEW_STORAGE_KEY, view)
    }
  }, [view])

  const typeName = entityTypeName ?? entityTypeSlug
  const rangeStart = offset + 1
  const rangeEnd = Math.min(offset + items.length, total)
  const sub =
    isLoading || isError || isUnknownType || total === 0
      ? ''
      : total > pageSize
        ? t('public.browse.subRange', { total, start: rangeStart, end: rangeEnd })
        : t(total === 1 ? 'public.browse.recordCount.one' : 'public.browse.recordCount.other', {
            count: total,
          })

  return (
    <div className="pagehead">
      <Link className="backlink" to="/">
        <IconArrowLeft size={16} /> {t('public.nav.allRecords')}
      </Link>
      <h1 className="pagehead__title">{typeName}</h1>
      {sub !== '' ? <p className="pagehead__sub">{sub}</p> : null}

      <div className="pagehead__row">
        <div className="filterchips">
          {entityTypes.map((type) => (
            <Link
              key={type.slug}
              className="chip"
              to={type.href}
              aria-current={type.slug === entityTypeSlug ? 'page' : undefined}
            >
              {type.name}
            </Link>
          ))}
        </div>
        <div className="viewtoggle" role="group" aria-label={t('public.browse.viewToggle.label')}>
          <button
            type="button"
            aria-pressed={view === 'list'}
            aria-label={t('public.browse.viewToggle.list')}
            onClick={() => {
              setView('list')
            }}
          >
            <IconList size={16} />
          </button>
          <button
            type="button"
            aria-pressed={view === 'grid'}
            aria-label={t('public.browse.viewToggle.grid')}
            onClick={() => {
              setView('grid')
            }}
          >
            <IconGrid size={16} />
          </button>
        </div>
      </div>

      {isLoading ? (
        <p className="searchhint">{t('public.browse.loading')}</p>
      ) : isError ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <h3 className="empty__title">{t('public.browse.error.title')}</h3>
          <p className="empty__text">{errorTitle ?? t('common.error.unknown')}</p>
          <button type="button" className="btn btn--ghost" onClick={onRetry}>
            {t('common.actions.retry')}
          </button>
        </div>
      ) : isUnknownType ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <span className="empty__icon">
            <IconInbox size={26} />
          </span>
          <h3 className="empty__title">{t('public.browse.unknownType.title')}</h3>
          <p className="empty__text">
            {t('public.browse.unknownType.description', { slug: entityTypeSlug })}
          </p>
          <Link className="btn btn--ghost" to="/">
            {t('public.nav.backToLatest')}
          </Link>
        </div>
      ) : items.length === 0 ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <span className="empty__icon">
            <IconInbox size={26} />
          </span>
          <h3 className="empty__title">
            {t('public.browse.empty.title', { type: typeName.toLowerCase() })}
          </h3>
          <p className="empty__text">{t('public.browse.empty.description')}</p>
          <Link className="btn btn--ghost" to="/">
            {t('public.nav.backToLatest')}
          </Link>
        </div>
      ) : (
        <>
          {view === 'grid' ? (
            <div className="cardgrid" style={{ marginTop: '2rem' }}>
              {items.map((item) => (
                <GridCard key={item.id} item={item} typeSlug={entityTypeSlug} typeName={typeName} />
              ))}
            </div>
          ) : (
            <div className="rowlist">
              {items.map((item) => (
                <ListRow key={item.id} item={item} typeSlug={entityTypeSlug} typeName={typeName} />
              ))}
            </div>
          )}
          {hasPreviousPage || hasNextPage ? (
            <nav className="pager" aria-label={t('public.browse.pager.label')}>
              <button
                type="button"
                aria-label={t('public.browse.pager.previous')}
                disabled={!hasPreviousPage}
                onClick={onPreviousPage}
              >
                <IconArrowLeft size={16} />
              </button>
              <button
                type="button"
                aria-label={t('public.browse.pager.next')}
                disabled={!hasNextPage}
                onClick={onNextPage}
              >
                <IconArrow size={16} />
              </button>
            </nav>
          ) : null}
        </>
      )}
    </div>
  )
}
