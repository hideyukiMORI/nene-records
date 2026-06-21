import { type RefObject, useEffect } from 'react'

/**
 * First-party scroll-reveal (#371 S1). Tags matching descendants of `containerRef`
 * with `data-motion-reveal-item` and, when each scrolls into view, adds
 * `data-revealed` (then stops observing it). The accompanying CSS animates the
 * transition and — crucially — only hides elements that carry BOTH the theme's
 * `data-motion-reveal` flag AND this JS-applied `data-motion-reveal-item`, so a
 * no-JS / no-IntersectionObserver visitor sees everything immediately.
 *
 * The caller gates `enabled` (off when the flag is off or reduced-motion is on).
 * `scanKey` (e.g. the route pathname) re-scans for freshly rendered targets.
 */
const ITEM_ATTR = 'data-motion-reveal-item'
const REVEALED_ATTR = 'data-revealed'

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
        for (const entry of entries) {
          if (entry.isIntersecting) {
            entry.target.setAttribute(REVEALED_ATTR, '')
            obs.unobserve(entry.target)
          }
        }
      },
      // Reveal slightly before fully in view; tiny threshold so tall items count.
      { rootMargin: '0px 0px -8% 0px', threshold: 0.04 },
    )

    root.querySelectorAll(selector).forEach((el) => {
      if (!el.hasAttribute(REVEALED_ATTR)) {
        el.setAttribute(ITEM_ATTR, '')
        observer.observe(el)
      }
    })

    return () => {
      observer.disconnect()
    }
  }, [containerRef, enabled, selector, scanKey])
}
