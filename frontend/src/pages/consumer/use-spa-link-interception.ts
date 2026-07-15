import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { getBasePath } from '@/shared/lib/base-path'
import { resolveSpaLink } from './resolve-spa-link'

/**
 * Route plain `<a href>` clicks through the SPA router (#885).
 *
 * A `bare`/bespoke page authors its own header and footer inside the sanitized
 * `html` field, where a React `<Link>` cannot exist — so every link is a plain
 * anchor and the browser does a full document load, remounting the whole SPA on
 * each navigation. This delegated listener gives that content the client-side
 * routing it cannot ask for itself, while {@see resolveSpaLink} keeps the browser
 * in charge of anything that is not an in-app navigation (other origins, media,
 * downloads, modified clicks).
 *
 * Attached at the public shell so it covers every public route, including `bare`
 * pages that never render the themed chrome.
 */
export function useSpaLinkInterception(): void {
  const navigate = useNavigate()

  useEffect(() => {
    const onClick = (event: MouseEvent) => {
      const target = event.target
      if (!(target instanceof Element)) {
        return
      }

      const anchor = target.closest('a')
      if (anchor === null) {
        return
      }

      const to = resolveSpaLink(anchor, event, {
        basePath: getBasePath(),
        origin: window.location.origin,
        currentPath: window.location.pathname,
      })

      if (to === null) {
        return
      }

      event.preventDefault()
      void navigate(to)
    }

    document.addEventListener('click', onClick)

    return () => {
      document.removeEventListener('click', onClick)
    }
  }, [navigate])
}
