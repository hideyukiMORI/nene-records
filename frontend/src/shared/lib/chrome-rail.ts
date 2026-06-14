import { useSyncExternalStore } from 'react'

/**
 * Tiny module-level signal so a deep feature (the layout preview) can ask the
 * app chrome to collapse its sidebar into an icon rail. Mirrors the prototype's
 * `app.rail` flag. Module store (like `authStore`) avoids threading a setter
 * through the route tree.
 */
let railMode = false
const listeners = new Set<() => void>()

export function setChromeRail(next: boolean): void {
  if (railMode === next) return
  railMode = next
  listeners.forEach((l) => {
    l()
  })
}

export function useChromeRail(): boolean {
  return useSyncExternalStore(
    (onChange) => {
      listeners.add(onChange)
      return () => {
        listeners.delete(onChange)
      }
    },
    () => railMode,
    () => false,
  )
}
