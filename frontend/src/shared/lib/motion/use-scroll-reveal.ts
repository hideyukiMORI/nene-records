import { type RefObject, useEffect } from 'react'

/**
 * First-party scroll-reveal (#371 S1). Tags matching descendants of `containerRef`
 * with `data-motion-reveal-item` and, when each scrolls into view, adds
 * `data-revealed` (then stops observing it). The accompanying CSS animates the
 * transition and — crucially — only hides elements that carry BOTH the theme's
 * `data-motion-reveal` flag AND this JS-applied `data-motion-reveal-item`, so a
 * no-JS / no-IntersectionObserver visitor sees everything immediately.
 *
 * Public feeds load their cards asynchronously (react-query), so the targets are
 * not in the DOM when the effect first runs. A MutationObserver picks up nodes
 * added after mount (and on route change). Items that enter the viewport in the
 * same batch get a small staggered delay so a grid row cascades in visibly.
 *
 * The caller gates `enabled` (off when the flag is off or reduced-motion is on).
 * `scanKey` (e.g. the route pathname) forces a fresh observer on navigation.
 */
const ITEM_ATTR = 'data-motion-reveal-item'
const REVEALED_ATTR = 'data-revealed'
const DELAY_VAR = '--motion-reveal-delay'
const STAGGER_STEP_MS = 80

export interface ScrollRevealOptions {
  containerRef: RefObject<HTMLElement | null>
  /** Reveal is active only when true (flag on AND not reduced-motion). */
  enabled: boolean
  /** CSS selector for the elements to reveal. */
  selector: string
  /** Changing this re-runs the scan (pass the route pathname). */
  scanKey?: string
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

    const observer = new IntersectionObserver(
      (entries, obs) => {
        let staggerIndex = 0
        for (const entry of entries) {
          if (!entry.isIntersecting) {
            continue
          }
          // Items revealing together cascade; one-at-a-time scrolling has no delay.
          if (entry.target instanceof HTMLElement) {
            entry.target.style.setProperty(DELAY_VAR, `${String(staggerIndex * STAGGER_STEP_MS)}ms`)
          }
          entry.target.setAttribute(REVEALED_ATTR, '')
          obs.unobserve(entry.target)
          staggerIndex += 1
        }
      },
      // Reveal slightly before fully in view; tiny threshold so tall items count.
      { rootMargin: '0px 0px -8% 0px', threshold: 0.04 },
    )

    const tagAndObserve = (): void => {
      root.querySelectorAll(selector).forEach((el) => {
        if (!el.hasAttribute(ITEM_ATTR) && !el.hasAttribute(REVEALED_ATTR)) {
          el.setAttribute(ITEM_ATTR, '')
          observer.observe(el)
        }
      })
    }

    // Tag what is already present, then keep up with async-rendered content.
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
    }
  }, [containerRef, enabled, selector, scanKey])
}
