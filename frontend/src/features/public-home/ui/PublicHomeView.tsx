import { useState } from 'react'
import { Link } from 'react-router-dom'
import { NeneMark } from '@/shared/ui'
import { IconMenu, IconMoon, IconSearch, IconSun, IconX } from '@/shared/ui/icons/Icons'
import { type HomeFeedItem, usePublicHomePage } from '../hooks/use-public-home-page'
import { type ThemeMode, useConsumerTheme } from '../model/use-consumer-theme'
import heroUrl from './assets/hero.png'
import { IconArrow, IconArrowUpRight, IconAuto, IconInbox } from './home-icons'
import './public-home.css'

export interface PublicHomeViewProps {
  siteName: string
  tagline: string
  metaDescription: string
  footerMarkdown: string
}

interface NavLink {
  label: string
  to: string
  current?: boolean
}

function ThemeSwitch({ mode, onMode }: { mode: ThemeMode; onMode: (mode: ThemeMode) => void }) {
  const options: Array<{ key: ThemeMode; label: string; icon: React.ReactNode }> = [
    { key: 'light', label: 'Light theme', icon: <IconSun size={16} /> },
    { key: 'dark', label: 'Dark theme', icon: <IconMoon size={16} /> },
    { key: 'auto', label: 'Match system', icon: <IconAuto size={16} /> },
  ]
  return (
    <div className="themesw" role="group" aria-label="Color theme">
      {options.map((option) => (
        <button
          key={option.key}
          type="button"
          className="themesw__btn"
          aria-pressed={mode === option.key}
          aria-label={option.label}
          title={option.label}
          onClick={() => {
            onMode(option.key)
          }}
        >
          {option.icon}
        </button>
      ))}
    </div>
  )
}

function Brand({ siteName, tagline }: { siteName: string; tagline?: string }) {
  return (
    <Link className="brand" to="/">
      <span className="brand__mark">
        <NeneMark size={22} />
      </span>
      <span className="brand__text">
        <span className="brand__name">{siteName}</span>
        {tagline !== undefined && tagline !== '' ? (
          <span className="brand__tag">{tagline}</span>
        ) : null}
      </span>
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
      <Link className="btn btn--ghost" to="/posts">
        Browse entity types
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

export function PublicHomeView({
  siteName,
  tagline,
  metaDescription,
  footerMarkdown,
}: PublicHomeViewProps) {
  const { mode, resolvedTheme, setMode } = useConsumerTheme()
  const [drawerOpen, setDrawerOpen] = useState(false)
  const {
    featured,
    rest,
    types,
    totalPublished,
    typeCount,
    isLoading,
    isError,
    errorTitle,
    refetch,
  } = usePublicHomePage()

  const navLinks: NavLink[] = [
    { label: 'Latest', to: '/', current: true },
    ...types.slice(0, 3).map((type) => ({ label: type.name, to: type.href })),
    { label: 'Search', to: '/search' },
  ]

  const footerTypeLinks = types.slice(0, 4)

  return (
    <div className="nene-home" data-theme={resolvedTheme} data-theme-mode={mode}>
      <header className="hd">
        <div className="wrap hd__in">
          <Brand siteName={siteName} tagline={tagline} />
          <nav className="hd__nav" aria-label="Primary">
            {navLinks.map((link) => (
              <Link
                key={`${link.label}-${link.to}`}
                className="navlink"
                to={link.to}
                aria-current={link.current === true ? 'page' : undefined}
              >
                {link.label}
              </Link>
            ))}
            <ThemeSwitch mode={mode} onMode={setMode} />
            <button
              type="button"
              className="iconbtn hd__menu"
              aria-label="Open menu"
              onClick={() => {
                setDrawerOpen(true)
              }}
            >
              <IconMenu size={18} />
            </button>
          </nav>
        </div>
      </header>

      <main>
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
                <Link className="btn btn--ghost" to="/posts">
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

        <div className="wrap">
          <div className="layout">
            <div className="layout__main">
              <section className="section" id="latest" aria-labelledby="latest-h">
                <div className="section__head">
                  <div>
                    <p className="eyebrow">Newest first</p>
                    <h2 className="section__title" id="latest-h">
                      Latest records
                    </h2>
                  </div>
                  <Link className="section__link" to="/posts">
                    All records <IconArrowUpRight size={15} />
                  </Link>
                </div>
                {isError ? (
                  <FeedError
                    title={errorTitle}
                    onRetry={() => {
                      void refetch()
                    }}
                  />
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
            </div>
          </div>
        </div>
      </main>

      <footer className="ft">
        <div className="wrap ft__grid">
          <div className="ft__brand">
            <Brand siteName={siteName} />
            {footerMarkdown !== '' ? <p className="ft__free">{footerMarkdown}</p> : null}
          </div>
          <div className="ft__col">
            <h4>Content</h4>
            <ul>
              <li>
                <Link to="/">Latest</Link>
              </li>
              {footerTypeLinks.map((type) => (
                <li key={type.slug}>
                  <Link to={type.href}>{type.name}</Link>
                </li>
              ))}
            </ul>
          </div>
          <div className="ft__col">
            <h4>Browse</h4>
            <ul>
              <li>
                <Link to="/search">
                  <IconSearch size={14} /> Search
                </Link>
              </li>
            </ul>
          </div>
        </div>
        <div className="wrap ft__bar">
          <small>
            © {new Date().getFullYear()} {siteName}
          </small>
          <small className="mono">Powered by NENE2 · {'>>'}</small>
        </div>
      </footer>

      <div className={`drawer ${drawerOpen ? 'is-open' : ''}`} aria-hidden={!drawerOpen}>
        <button
          type="button"
          className="drawer__scrim"
          aria-label="Close menu"
          tabIndex={drawerOpen ? 0 : -1}
          onClick={() => {
            setDrawerOpen(false)
          }}
        />
        <div className="drawer__panel" role="dialog" aria-label="Menu">
          <div className="drawer__head">
            <span className="eyebrow">Menu</span>
            <button
              type="button"
              className="iconbtn"
              aria-label="Close menu"
              onClick={() => {
                setDrawerOpen(false)
              }}
            >
              <IconX size={18} />
            </button>
          </div>
          {navLinks.map((link) => (
            <Link
              key={`drawer-${link.label}-${link.to}`}
              className="navlink"
              to={link.to}
              aria-current={link.current === true ? 'page' : undefined}
              onClick={() => {
                setDrawerOpen(false)
              }}
            >
              {link.label}
            </Link>
          ))}
          <div style={{ marginTop: 'auto' }}>
            <ThemeSwitch mode={mode} onMode={setMode} />
          </div>
        </div>
      </div>
    </div>
  )
}
