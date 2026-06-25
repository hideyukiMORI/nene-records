import { useEffect, useRef } from 'react'
import { useLocation } from 'react-router-dom'
import { trackPageView, type WebAnalyticsClientConfig } from './web-analytics'

/**
 * Report a page view on client-side route changes within the public site.
 *
 * The initial load is already counted by the server-injected GA4 / GTM snippet
 * (its `config` / `gtm.js` fires the first page view), so the first render is
 * skipped to avoid a double count; every subsequent SPA navigation reports the
 * new path. Config is read through a ref so a late-settling settings query does
 * not itself trigger a spurious view.
 */
export function useAnalyticsPageView(config: WebAnalyticsClientConfig): void {
  const location = useLocation()
  const configRef = useRef(config)
  const isFirstRender = useRef(true)

  useEffect(() => {
    configRef.current = config
  }, [config])

  useEffect(() => {
    if (isFirstRender.current) {
      isFirstRender.current = false

      return
    }

    trackPageView(configRef.current, {
      path: location.pathname + location.search,
      location: window.location.href,
      title: document.title,
    })
  }, [location.pathname, location.search])
}
