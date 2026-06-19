import { useEffect } from 'react'
import { type Blocker, useBlocker } from 'react-router-dom'

/**
 * Warn before losing unsaved work. While `when` is true it blocks in-app route
 * changes (returns a Blocker the caller renders a confirm dialog for) and warns
 * the browser on tab close / reload via `beforeunload` (#462).
 */
export function useUnsavedChangesGuard(when: boolean): Blocker {
  const blocker = useBlocker(
    ({ currentLocation, nextLocation }) =>
      when && currentLocation.pathname !== nextLocation.pathname,
  )

  // If the changes get saved/discarded while a block is pending, release it.
  useEffect(() => {
    if (!when && blocker.state === 'blocked') {
      blocker.reset()
    }
  }, [when, blocker])

  useEffect(() => {
    if (!when) {
      return
    }
    // preventDefault() is the modern way to trigger the browser's leave prompt
    // (the legacy event.returnValue is deprecated).
    const handler = (event: BeforeUnloadEvent) => {
      event.preventDefault()
    }
    window.addEventListener('beforeunload', handler)
    return () => {
      window.removeEventListener('beforeunload', handler)
    }
  }, [when])

  return blocker
}
