import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { IconArrow, IconArrowUpRight, IconInbox } from '@/shared/ui/icons/magazine-icons'
import { LoadingFeatured } from '@/shared/ui/loading'
import type { HomeFeedItem, HomeTypeItem } from '../hooks/use-public-home-page'
import heroUrl from './assets/hero.png'

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

function TypeBadge({ slug, name }: { slug: string; name: string }) {
  return (
    <Link className="tbadge" to={`/${slug}`}>
      {name.toLowerCase()}
    </Link>
  )
}

function Featured({ item }: { item: HomeFeedItem }) {
  const { t } = useTranslation()
  return (
    <article className="featured">
      <Eyecatch label={item.eyecatchLabel} className="featured__media" />
      <div className="featured__body">
        <div className="featured__metarow">
          <TypeBadge slug={item.typeSlug} name={item.typeName} />
          {item.publishedLabel !== '' ? <span className="meta">{item.publishedLabel}</span> : null}
        </div>
        <h3 className="featured__title">
          <Link to={item.href}>{item.title}</Link>
        </h3>
        {item.excerpt !== '' ? <p className="featured__excerpt">{item.excerpt}</p> : null}
        <div className="featured__foot">
          <Link className="section__link" to={item.href}>
            {t('public.home.readArticle')} <IconArrow size={15} />
          </Link>
        </div>
      </div>
    </article>
  )
}

function ArticleCard({ item }: { item: HomeFeedItem }) {
  return (
    <article className="card">
      <Link to={item.href}>
        <Eyecatch label={item.eyecatchLabel} className="card__media" />
      </Link>
      <div className="card__metarow">
        <TypeBadge slug={item.typeSlug} name={item.typeName} />
        {item.publishedLabel !== '' ? <span className="meta">{item.publishedLabel}</span> : null}
      </div>
      <h3 className="card__title">
        <Link to={item.href}>{item.title}</Link>
      </h3>
      {item.excerpt !== '' ? <p className="card__excerpt">{item.excerpt}</p> : null}
    </article>
  )
}

function EmptyFeed() {
  const { t } = useTranslation()
  return (
    <div className="empty">
      <span className="empty__icon">
        <IconInbox size={26} />
      </span>
      <h3 className="empty__title">{t('public.home.empty.title')}</h3>
      <p className="empty__text">{t('public.home.empty.description')}</p>
      <Link className="btn btn--ghost" to="/search">
        {t('public.home.empty.searchCta')}
      </Link>
    </div>
  )
}

function FeedError({ title, onRetry }: { title: string | null; onRetry: () => void }) {
  const { t } = useTranslation()
  return (
    <div className="empty">
      <h3 className="empty__title">{t('public.home.error.title')}</h3>
      <p className="empty__text">{title ?? t('public.home.error.fallback')}</p>
      <button type="button" className="btn btn--ghost" onClick={onRetry}>
        {t('common.actions.retry')}
      </button>
    </div>
  )
}

export interface PublicHomeHeroProps {
  siteName: string
  metaDescription: string
  totalPublished: number
  typeCount: number
  browseHref: string
}

/** Full-bleed magazine masthead. Rendered into the shell's `hero` slot. */
export function PublicHomeHero({
  siteName,
  metaDescription,
  totalPublished,
  typeCount,
  browseHref,
}: PublicHomeHeroProps) {
  const { t } = useTranslation()
  return (
    <section className="wrap hero" aria-labelledby="hero-title">
      <div className="hero__grid">
        <div className="hero__copy">
          <p className="eyebrow hero__kicker">{t('public.home.hero.kicker', { siteName })}</p>
          <h1 className="hero__title" id="hero-title">
            {t('public.home.hero.titleLine1')}
            <br />
            <em>{t('public.home.hero.titleEmphasis')}</em> {t('public.home.hero.titleRest')}
          </h1>
          {metaDescription !== '' ? <p className="hero__lead">{metaDescription}</p> : null}
          <div className="hero__cta">
            <Link className="btn btn--primary" to="#latest">
              {t('public.home.hero.ctaLatest')} <IconArrow size={17} />
            </Link>
            <Link className="btn btn--ghost" to={browseHref}>
              {t('public.home.browseByType')}
            </Link>
          </div>
          <div className="hero__meta">
            <div className="hero__stat">
              <b>{totalPublished}</b>
              <span>{t('public.home.hero.statRecords')}</span>
            </div>
            <div className="hero__stat">
              <b>{typeCount}</b>
              <span>{t('public.home.hero.statTypes')}</span>
            </div>
            <div className="hero__stat">
              <b>60+</b>
              <span>{t('public.home.hero.statMcp')}</span>
            </div>
          </div>
        </div>
        <div className="hero__art">
          <div className="hero__art-frame">
            <img src={heroUrl} alt={t('public.home.hero.artAlt')} />
            <span className="hero__art-tag">
              <b>{'>>'}</b> {t('public.home.hero.artTag')}
            </span>
          </div>
        </div>
      </div>
    </section>
  )
}

export interface PublicHomeBodyProps {
  featured: HomeFeedItem | null
  rest: HomeFeedItem[]
  types: HomeTypeItem[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

/** Latest cross-type feed + entity-type entrances. Rendered into the shell. */
export function PublicHomeBody({
  featured,
  rest,
  types,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: PublicHomeBodyProps) {
  const { t } = useTranslation()
  return (
    <>
      <section className="section" id="latest" aria-labelledby="latest-h">
        <div className="section__head">
          <div>
            <p className="eyebrow">{t('public.home.latest.eyebrow')}</p>
            <h2 className="section__title" id="latest-h">
              {t('public.home.latest.title')}
            </h2>
          </div>
          {types[0] !== undefined ? (
            <Link className="section__link" to={types[0].href}>
              {t('public.nav.allRecords')} <IconArrowUpRight size={15} />
            </Link>
          ) : null}
        </div>
        {isError ? (
          <FeedError title={errorTitle} onRetry={onRetry} />
        ) : isLoading ? (
          <LoadingFeatured count={3} />
        ) : featured === null ? (
          <EmptyFeed />
        ) : (
          <>
            <Featured item={featured} />
            {rest.length > 0 ? (
              <div className="cardgrid">
                {rest.map((item) => (
                  <ArticleCard key={item.id} item={item} />
                ))}
              </div>
            ) : null}
          </>
        )}
      </section>

      {types.length > 0 ? (
        <section className="section" aria-labelledby="types-h">
          <div className="section__head">
            <div>
              <p className="eyebrow">{t('public.home.types.eyebrow')}</p>
              <h2 className="section__title" id="types-h">
                {t('public.home.browseByType')}
              </h2>
            </div>
          </div>
          <div className="types">
            {types.map((type) => (
              <Link key={type.slug} className="typecard" to={type.href}>
                <span className="typecard__main">
                  <span className="typecard__name">{type.name}</span>
                  <span className="typecard__slug">/{type.slug}</span>
                </span>
                <span className="typecard__arrow">
                  <IconArrow size={20} />
                </span>
              </Link>
            ))}
          </div>
        </section>
      ) : null}
    </>
  )
}
