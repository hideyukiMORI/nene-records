import { type RefObject, useEffect } from 'react'

/**
 * First-party scroll-reveal (#371 S1). Tags matching descendants of `containerRef`
 * with `data-motion-reveal-item` and reveals them (`data-revealed`) so the CSS can
 * fade/slide them in. CSS only hides elements that carry BOTH the theme's
 * `data-motion-reveal` flag AND this JS-applied `data-motion-reveal-item`, so a
 * no-JS / no-IntersectionObserver visitor sees everything immediately.
 *
 * Resilience rules (a reveal must never strand content hidden):
 *  - Anything already in or above the viewport is revealed right away — this
 *    covers above-the-fold content and SPA back-navigation (the shell remounts
 *    with cached content at a restored scroll position, where IntersectionObserver
 *    would otherwise not re-fire and leave cards stuck at opacity 0).
 *  - Only genuinely below-the-fold elements are deferred to the IntersectionObserver
 *    for a scroll-triggered reveal.
 *  - Feeds load asynchronously (react-query); a MutationObserver picks up nodes
 *    added after mount and on route change.
 *
 * Items revealed in the same batch get a small staggered delay so a grid row
 * cascades in visibly. The caller gates `enabled` (off when the flag is off or
 * reduced-motion is on); `scanKey` (route pathname) forces a fresh observer.
 */
const ITEM_ATTR = 'data-motion-reveal-item'
const REVEALED_ATTR = 'data-revealed'
const DELAY_VAR = '--motion-reveal-delay'
const STAGGER_STEP_MS = 110

export interface ScrollRevealOptions {
  containerRef: RefObject<HTMLElement | null>
  /** Reveal is active only when true (flag on AND not reduced-motion). */
  enabled: boolean
  /** CSS selector for the elements to reveal. */
  selector: string
  /** Changing this re-runs the scan (pass the route pathname). */
  scanKey?: string
}

function reveal(el: Element, delayMs: number): void {
  if (el instanceof HTMLElement) {
    el.style.setProperty(DELAY_VAR, `${String(delayMs)}ms`)
  }
  el.setAttribute(REVEALED_ATTR, '')
}

export function useScrollReveal({
  containerRef,
  enabled,
  selector,
  scanKey,
}: ScrollRevealOptions): void {
  useEffect(() => {
    const root = containerRef.current
    if (!enabled || root === null || typeof IntersectionObserver === 'undefined') {
      return undefined
    }

    const rafIds: number[] = []

    const observer = new IntersectionObserver(
      (entries, obs) => {
        let staggerIndex = 0
        for (const entry of entries) {
          if (!entry.isIntersecting) {
            continue
          }
          reveal(entry.target, staggerIndex * STAGGER_STEP_MS)
          obs.unobserve(entry.target)
          staggerIndex += 1
        }
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.04 },
    )

    const tagAndObserve = (): void => {
      const inView: Element[] = []
      root.querySelectorAll(selector).forEach((el) => {
        // Skip only items that are already revealed. A tagged-but-unrevealed
        // item must be re-processed: under React StrictMode (and on remount) the
        // effect runs, its cleanup cancels the pending reveal / disconnects the
        // observer, then it runs again — if we skipped on the tag alone the item
        // would stay at opacity 0 forever (the home feed vanishing on back-nav).
        if (el.hasAttribute(REVEALED_ATTR)) {
          return
        }
        el.setAttribute(ITEM_ATTR, '')
        // Reveal anything not strictly below the fold straight away; defer the
        // rest to scroll. window.innerHeight is safe here (browser-only effect).
        if (el.getBoundingClientRect().top < window.innerHeight) {
          inView.push(el)
        } else {
          observer.observe(el)
        }
      })

      if (inView.length > 0) {
        // One frame later, so the opacity:0 initial state has painted and the
        // reveal animates rather than snapping.
        const id = requestAnimationFrame(() => {
          inView.forEach((el, index) => {
            reveal(el, index * STAGGER_STEP_MS)
          })
        })
        rafIds.push(id)
      }
    }

    tagAndObserve()
    const mutationObserver =
      typeof MutationObserver === 'undefined'
        ? null
        : new MutationObserver(() => {
            tagAndObserve()
          })
    mutationObserver?.observe(root, { childList: true, subtree: true })

    return () => {
      observer.disconnect()
      mutationObserver?.disconnect()
      rafIds.forEach((id) => {
        cancelAnimationFrame(id)
      })
    }
  }, [containerRef, enabled, selector, scanKey])
}
