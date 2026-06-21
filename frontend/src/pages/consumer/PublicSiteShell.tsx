import { type ReactNode, useMemo, useRef, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useEntityTypeList } from '@/entities/entity-type'
import { usePublicWidgets } from '@/entities/widget'
import { SiteWidgets } from '@/features/render-widgets'
import { hasCta, hasTopbarContent, safeHref } from '@/shared/lib/header-config'
import { useHeaderShrink } from '@/shared/lib/motion/use-header-shrink'
import { useScrollReveal } from '@/shared/lib/motion/use-scroll-reveal'
import { NeneMark } from '@/shared/ui'
import { IconMenu, IconMoon, IconSearch, IconSun, IconX } from '@/shared/ui/icons/Icons'
import { IconAuto } from '@/shared/ui/icons/magazine-icons'
import './public-site.css'
import type { HeaderCta, HeaderTopbar } from '@/shared/lib/header-config'
import { useConsumerMotion } from './use-consumer-motion'
import type { PublicSite } from './public-site-context'
import { type ThemeMode, useConsumerTheme } from './use-consumer-theme'

/** Repeating content blocks that scroll-reveal targets (#371 S1). */
const REVEAL_SELECTOR = '.card, .typecard, .section__head, .article__head'

export interface PublicSiteShellProps {
  site: PublicSite
  /** Slug of the active entity type, for nav `aria-current` on browse/record. */
  activeTypeSlug?: string | null
  /** True on the home route so "Latest" gets `aria-current`. */
  isHome?: boolean
  /** Full-bleed content rendered before the layout wrap (home hero). */
  hero?: ReactNode
  /**
   * Whether the shell renders the `sidebar` region widgets as a second column.
   * Defaults to true for listing/home pages. Record detail opts in only for
   * multi-column layouts; single-column records pass false so the article spans
   * the full reading width.
   */
  withSidebar?: boolean
  /**
   * Whether the shell renders the `aside` region widgets as a third column.
   * Only the `three-col` record layout opts in; an empty `aside` region stays
   * collapsed so the layout falls back to two (or one) columns.
   */
  withAside?: boolean
  children: ReactNode
}

interface ShellNavLink {
  label: string
  to: string
  current: boolean
}

function ThemeSwitch({ mode, onMode }: { mode: ThemeMode; onMode: (mode: ThemeMode) => void }) {
  const options: Array<{ key: ThemeMode; label: string; icon: ReactNode }> = [
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

/** Thin Top bar above the main header: free text + phone/email contact links. */
function HeaderTopbarRow({ topbar }: { topbar: HeaderTopbar }) {
  return (
    <div className="hd-topbar">
      <div className="wrap hd-topbar__in">
        {topbar.infoText !== '' ? <span className="hd-topbar__info">{topbar.infoText}</span> : null}
        <span className="hd-topbar__contact">
          {topbar.phone !== '' ? (
            <a href={`tel:${topbar.phone.replace(/\s+/g, '')}`}>{topbar.phone}</a>
          ) : null}
          {topbar.email !== '' ? <a href={`mailto:${topbar.email}`}>{topbar.email}</a> : null}
        </span>
      </div>
    </div>
  )
}

/** Header CTA button — a primary call-to-action link in the action cluster. */
function HeaderCtaButton({ cta }: { cta: HeaderCta }) {
  return (
    <a className="btn btn--primary hd__cta" href={safeHref(cta.url)}>
      {cta.label}
    </a>
  )
}

function Brand({ siteName, tagline, logo }: { siteName: string; tagline?: string; logo?: string }) {
  return (
    <Link className="brand" to="/">
      <span className="brand__mark">
        {logo !== undefined && logo !== '' ? (
          <img className="brand__logo" src={logo} alt={siteName} />
        ) : (
          <NeneMark size={22} />
        )}
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

/** Footer copyright: substitute `{year}`/`{site}` tokens, fall back to a default. */
function renderCopyright(template: string, siteName: string): string {
  const year = String(new Date().getFullYear())
  if (template.trim() === '') {
    return `© ${year} ${siteName}`
  }
  return template.replaceAll('{year}', year).replaceAll('{site}', siteName)
}

/**
 * PublicSiteShell — the shared magazine chrome for every public route: sticky
 * header (brand, type nav, search, light/dark/auto theme toggle), mobile drawer,
 * the 1-/2-column content layout, and the footer. Pages render their body into
 * `children`; the home page additionally passes a full-bleed `hero`.
 *
 * Theme is resolved client-side here (`useConsumerTheme`) and applied via
 * `data-theme` / `data-theme-mode` on the scope root, the same contract slice 1
 * established for the home page.
 */
export function PublicSiteShell({
  site,
  activeTypeSlug = null,
  isHome = false,
  hero,
  withSidebar = true,
  withAside = false,
  children,
}: PublicSiteShellProps) {
  const { mode, resolvedTheme, setMode } = useConsumerTheme(site.activeTheme)
  const [drawerOpen, setDrawerOpen] = useState(false)

  // Motion capability layer (#371): the theme declares `data-motion-reveal`;
  // first-party JS implements scroll-reveal, gated by prefers-reduced-motion.
  const mainRef = useRef<HTMLElement>(null)
  const sentinelRef = useRef<HTMLDivElement>(null)
  const { pathname } = useLocation()
  const motion = useConsumerMotion(site.themeFlagAttrs)
  useScrollReveal({
    containerRef: mainRef,
    enabled: motion.reveal !== 'off',
    selector: REVEAL_SELECTOR,
    scanKey: pathname,
  })
  const headerShrunk = useHeaderShrink({
    enabled: motion.header === 'shrink',
    sentinelRef,
  })

  // Render `sidebar` / `aside` regions as side columns when widgets are placed
  // there (and the page opts in). Empty regions collapse, so the grid steps down
  // from three → two → single column as content allows.
  const widgetQuery = usePublicWidgets()
  const widgets = widgetQuery.data?.items ?? []
  const hasSidebar = withSidebar && widgets.some((widget) => widget.region === 'sidebar')
  const hasAside = withAside && widgets.some((widget) => widget.region === 'aside')
  const sideColumns = (hasSidebar ? 1 : 0) + (hasAside ? 1 : 0)
  const layoutModifier = sideColumns === 2 ? 'is-3col' : sideColumns === 1 ? 'is-2col' : ''

  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })
  const types = useMemo(
    () =>
      (entityTypeQuery.data?.items ?? []).map((type) => ({
        slug: type.slug,
        name: type.name,
        href: `/${type.slug}`,
      })),
    [entityTypeQuery.data?.items],
  )

  const navLinks: ShellNavLink[] = [
    { label: 'Latest', to: '/', current: isHome },
    ...types.slice(0, 3).map((type) => ({
      label: type.name,
      to: type.href,
      current: !isHome && type.slug === activeTypeSlug,
    })),
    { label: 'Search', to: '/search', current: false },
  ]

  const footerTypeLinks = types.slice(0, 4)

  return (
    <div
      className="nene-public"
      data-theme={resolvedTheme}
      data-theme-mode={mode}
      {...site.themeFlagAttrs}
      data-shrunk={headerShrunk ? '' : undefined}
    >
      {/* Zero-height marker at the page top; when it scrolls away the sticky
          header compacts (motionHeader='shrink'). See use-header-shrink. */}
      <div ref={sentinelRef} className="motion-sentinel" aria-hidden="true" />
      {site.runtimeThemeCss !== '' ? <style>{site.runtimeThemeCss}</style> : null}
      {site.themeOverrideCss !== '' ? <style>{site.themeOverrideCss}</style> : null}
      {hasTopbarContent(site.headerConfig.topbar) ? (
        <HeaderTopbarRow topbar={site.headerConfig.topbar} />
      ) : null}
      <header className="hd">
        <div className="wrap hd__in">
          <Brand siteName={site.siteName} tagline={site.tagline} logo={site.logo} />
          <nav className="hd__nav" aria-label="Primary">
            {navLinks.map((link) => (
              <Link
                key={`${link.label}-${link.to}`}
                className="navlink"
                to={link.to}
                aria-current={link.current ? 'page' : undefined}
              >
                {link.label}
              </Link>
            ))}
          </nav>
          <div className="hd__actions">
            {hasCta(site.headerConfig.cta) ? <HeaderCtaButton cta={site.headerConfig.cta} /> : null}
            <Link className="iconbtn hd__search" to="/search" aria-label="Search" title="Search">
              <IconSearch size={18} />
            </Link>
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
          </div>
        </div>
      </header>

      <main ref={mainRef}>
        {hero}
        <div className="wrap">
          <div className={`layout ${layoutModifier}`}>
            <div className="layout__main">{children}</div>
            {hasSidebar ? (
              <aside className="aside" aria-label="Site widgets">
                <SiteWidgets region="sidebar" />
              </aside>
            ) : null}
            {hasAside ? (
              <aside className="aside" aria-label="Secondary widgets">
                <SiteWidgets region="aside" />
              </aside>
            ) : null}
          </div>
        </div>
      </main>

      <footer className="ft">
        <div className="wrap ft__grid">
          <div className="ft__brand">
            <Brand siteName={site.siteName} logo={site.logo} />
            {site.footerMarkdown !== '' ? <p className="ft__free">{site.footerMarkdown}</p> : null}
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
          <small>{renderCopyright(site.copyrightText, site.siteName)}</small>
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
              aria-current={link.current ? 'page' : undefined}
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
