import './route-progress.css'

/**
 * The public site's "still loading" signal, for the states where the page's
 * **layout is not yet known** (#894).
 *
 * Renders a viewport-anchored top bar plus three faint centre dots — and nothing
 * else. It must stay layout-independent: at these points we cannot tell whether
 * the page is `standard` (themed chrome) or `bare` (no theme, author-supplied
 * design), and painting chrome we then rip away is what made every bespoke page
 * flash a foreign header (#879/#881/#883/#887). The note at the call sites puts
 * it as: a wrong layout reads as a broken site; a blank reads as loading. So the
 * body stays blank and only this floats above it.
 *
 * One instance covers four states: the permalink resolve, the record-id resolve,
 * the type resolve, and the lazy route chunk (`PublicShell`'s Suspense fallback).
 *
 * The dots carry no text on purpose: no message to translate, and no webfont to
 * fetch at the very moment we are already waiting on the network.
 */
export function RouteProgress({
  /**
   * Active theme id. Resolves --color-accent / --color-text-muted: built-in themes
   * are `[data-theme='x']`-scoped and this sits outside `.nene-public`, so without
   * it no token resolves and the `var()` fallbacks (currentColor) take over.
   * Passed explicitly rather than read from context because the Suspense fallback
   * renders *outside* the Outlet that provides the site.
   */
  theme,
  /** Data arrived — fill to 100% and fade out instead of creeping. */
  complete = false,
}: {
  theme?: string | undefined
  complete?: boolean
}) {
  return (
    <div className="nene-route-host" data-theme={theme}>
      <div
        className={`nene-route-progress${complete ? ' is-complete' : ''}`}
        role="progressbar"
        aria-hidden="true"
      >
        <div className="nene-route-progress__bar" />
      </div>
      <div className="nene-route-label nene-route-label--center">
        <div className="nene-dots" role="status" aria-label="Loading">
          <i />
          <i />
          <i />
        </div>
      </div>
    </div>
  )
}
