import { type RefObject, useEffect, useState } from 'react'

/**
 * First-party sticky-header shrink (#371 S2). Observes a zero-height sentinel at
 * the very top of the page; once the reader scrolls past a small threshold the
 * sentinel leaves the (margin-expanded) root and we report `shrunk = true`, which
 * the shell reflects as `data-shrunk` so CSS can compact the sticky header.
 *
 * Sentinel + IntersectionObserver rather than a scroll listener (no per-frame
 * work). The caller gates `enabled` (off when the flag is off or reduced-motion
 * is on). SSR-safe — returns `false` when IntersectionObserver is unavailable.
 */
// Positive top margin grows the root upward, so the top sentinel keeps
// intersecting until the page is scrolled past this threshold.
const THRESHOLD_PX = 72

export interface HeaderShrinkOptions {
  enabled: boolean
  sentinelRef: RefObject<Element | null>
}

export function useHeaderShrink({ enabled, sentinelRef }: HeaderShrinkOptions): boolean {
  const [shrunk, setShrunk] = useState(false)

  useEffect(() => {
    const sentinel = sentinelRef.current
    if (!enabled || sentinel === null || typeof IntersectionObserver === 'undefined') {
      setShrunk(false)
      return undefined
    }

    const observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0]
        if (entry !== undefined) {
          setShrunk(!entry.isIntersecting)
        }
      },
      { rootMargin: `${String(THRESHOLD_PX)}px 0px 0px 0px`, threshold: 0 },
    )
    observer.observe(sentinel)

    return () => {
      observer.disconnect()
    }
  }, [enabled, sentinelRef])

  return shrunk
}
