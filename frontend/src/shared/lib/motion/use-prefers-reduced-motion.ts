import { useEffect, useState } from 'react'

/**
 * Live `prefers-reduced-motion: reduce` state.
 *
 * The single source of truth for the motion capability layer (#371): every
 * first-party motion effect gates on this so reduced-motion users get no
 * animation. SSR-safe — returns `false` when `window`/`matchMedia` is absent.
 */
const REDUCE_QUERY = '(prefers-reduced-motion: reduce)'

function prefersReducedMotion(): boolean {
  return typeof window !== 'undefined' && typeof window.matchMedia === 'function'
    ? window.matchMedia(REDUCE_QUERY).matches
    : false
}

export function usePrefersReducedMotion(): boolean {
  // Initialise synchronously so the first render already matches the OS setting.
  const [reduced, setReduced] = useState<boolean>(prefersReducedMotion)

  useEffect(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
      return undefined
    }
    const mq = window.matchMedia(REDUCE_QUERY)
    const onChange = (): void => {
      setReduced(mq.matches)
    }
    mq.addEventListener('change', onChange)
    return () => {
      mq.removeEventListener('change', onChange)
    }
  }, [])

  return reduced
}
