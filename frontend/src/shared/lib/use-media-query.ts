import { useSyncExternalStore } from 'react'

/**
 * Subscribe to a CSS media query. Used to gate desktop-only chrome (e.g. the
 * preview icon rail) so it never fights the mobile drawer.
 */
export function useMediaQuery(query: string): boolean {
  return useSyncExternalStore(
    (onChange) => {
      const mql = window.matchMedia(query)
      mql.addEventListener('change', onChange)
      return () => {
        mql.removeEventListener('change', onChange)
      }
    },
    () => window.matchMedia(query).matches,
    () => false,
  )
}
