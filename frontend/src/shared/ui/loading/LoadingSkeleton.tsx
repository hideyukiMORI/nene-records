/**
 * Public-site loading skeletons (#894) — for the states where the layout is
 * already known, so the themed chrome is on screen and only the content area is
 * still waiting.
 *
 * These are `.nene-public`-scoped (styles live in `pages/consumer/public-site.css`),
 * unlike the layout-independent `RouteProgress`, which must NOT be scoped because
 * it renders before we know whether the page is themed at all.
 *
 * Each shape mirrors the real content that replaces it, so the swap doesn't move
 * the page. They fade in after 140ms: a fast response never shows them at all.
 */

/** Card grid — the type / tag / date archives, which all render the same list. */
export function LoadingCardGrid({ count = 6 }: { count?: number }) {
  return (
    <div className="loading-view" aria-busy="true">
      <div className="skeleton sk-count" />
      <div className="sk-grid">
        {Array.from({ length: count }, (_, i) => (
          <div className="sk-card" key={i}>
            <div className="skeleton sk-card__media" />
            <div className="skeleton sk-card__badge" />
            <div className="skeleton sk-card__title" />
            <div className="skeleton sk-card__title sk-card__title--short" />
          </div>
        ))}
      </div>
    </div>
  )
}

/** Three text rows — lighter than cards, for search-as-you-type. */
export function LoadingRows() {
  return (
    <div className="loading-view" aria-busy="true">
      <div className="sk-rows">
        <div className="skeleton sk-row sk-row--1" />
        <div className="skeleton sk-row sk-row--2" />
        <div className="skeleton sk-row sk-row--3" />
      </div>
    </div>
  )
}

/** An article body — the record detail: a heading bar, then paragraph groups (#905). */
export function LoadingArticle() {
  return (
    <div className="loading-view" aria-busy="true">
      <div className="sk-article">
        <div className="skeleton sk-article__title" />
        {[0, 1].map((p) => (
          <div className="sk-para" key={p}>
            <div className="skeleton sk-row" />
            <div className="skeleton sk-row sk-row--2" />
            <div className="skeleton sk-row sk-row--3" />
          </div>
        ))}
      </div>
    </div>
  )
}

/** A featured masthead above a card grid — the home feed. */
export function LoadingFeatured({ count = 3 }: { count?: number }) {
  return (
    <div className="loading-view" aria-busy="true">
      <div className="skeleton sk-featured" />
      <div className="sk-grid">
        {Array.from({ length: count }, (_, i) => (
          <div className="sk-card" key={i}>
            <div className="skeleton sk-card__media" />
            <div className="skeleton sk-card__title" />
          </div>
        ))}
      </div>
    </div>
  )
}
