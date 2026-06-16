import { Link } from 'react-router-dom'
import { IconArrow, IconArrowUpRight, IconInbox } from '@/shared/ui/icons/magazine-icons'
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
            Read article <IconArrow size={15} />
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
  return (
    <div className="empty">
      <span className="empty__icon">
        <IconInbox size={26} />
      </span>
      <h3 className="empty__title">No published records yet</h3>
      <p className="empty__text">
        When records are published they appear here, newest first. Draft and scheduled records stay
        hidden until their publish time passes.
      </p>
      <Link className="btn btn--ghost" to="/search">
        Search records
      </Link>
    </div>
  )
}

function FeedError({ title, onRetry }: { title: string | null; onRetry: () => void }) {
  return (
    <div className="empty">
      <h3 className="empty__title">Couldn’t load the latest records</h3>
      <p className="empty__text">{title ?? 'Something went wrong. Please try again.'}</p>
      <button type="button" className="btn btn--ghost" onClick={onRetry}>
        Retry
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
  return (
    <section className="wrap hero" aria-labelledby="hero-title">
      <div className="hero__grid">
        <div className="hero__copy">
          <p className="eyebrow hero__kicker">{siteName} — publishing platform</p>
          <h1 className="hero__title" id="hero-title">
            One record.
            <br />
            <em>Every</em> reader.
          </h1>
          {metaDescription !== '' ? <p className="hero__lead">{metaDescription}</p> : null}
          <div className="hero__cta">
            <Link className="btn btn--primary" to="#latest">
              Read the latest <IconArrow size={17} />
            </Link>
            <Link className="btn btn--ghost" to={browseHref}>
              Browse by type
            </Link>
          </div>
          <div className="hero__meta">
            <div className="hero__stat">
              <b>{totalPublished}</b>
              <span>published records</span>
            </div>
            <div className="hero__stat">
              <b>{typeCount}</b>
              <span>entity types</span>
            </div>
            <div className="hero__stat">
              <b>60+</b>
              <span>MCP tools</span>
            </div>
          </div>
        </div>
        <div className="hero__art">
          <div className="hero__art-frame">
            <img src={heroUrl} alt="Typed records, published everywhere" />
            <span className="hero__art-tag">
              <b>{'>>'}</b> typed · readable · agent-ready
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
  return (
    <>
      <section className="section" id="latest" aria-labelledby="latest-h">
        <div className="section__head">
          <div>
            <p className="eyebrow">Newest first</p>
            <h2 className="section__title" id="latest-h">
              Latest records
            </h2>
          </div>
          {types[0] !== undefined ? (
            <Link className="section__link" to={types[0].href}>
              All records <IconArrowUpRight size={15} />
            </Link>
          ) : null}
        </div>
        {isError ? (
          <FeedError title={errorTitle} onRetry={onRetry} />
        ) : isLoading ? (
          <p className="meta">Loading latest records…</p>
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
              <p className="eyebrow">Entrances</p>
              <h2 className="section__title" id="types-h">
                Browse by type
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
