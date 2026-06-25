/**
 * Client-side counterpart to the server's `WebAnalyticsConfig` / head snippet
 * (PR-A1). The server injects the GA4 / GTM loader and the Consent Mode v2
 * *default* into the initial HTML; this module handles the parts that only the
 * SPA can do: reporting client-side route changes and updating consent when the
 * visitor responds to the banner.
 *
 * Tag ids are validated to the same strict shape as the backend so a malformed
 * setting simply disables analytics (rather than emitting a broken tag).
 */

const ID_PATTERN = /^[A-Za-z0-9_-]{4,40}$/
const CONSENT_STORAGE_KEY = 'nene-records:analytics-consent'

export type ConsentChoice = 'granted' | 'denied'

export interface WebAnalyticsClientConfig {
  gtmId: string | null
  ga4Id: string | null
  consentDefault: ConsentChoice
  enabled: boolean
}

declare global {
  interface Window {
    dataLayer?: unknown[]
    gtag?: (...args: unknown[]) => void
  }
}

function normalizeId(raw: string | undefined): string | null {
  const trimmed = (raw ?? '').trim()

  return ID_PATTERN.test(trimmed) ? trimmed : null
}

/** Resolve the analytics config from the public settings map. */
export function resolveWebAnalytics(settings: Record<string, string>): WebAnalyticsClientConfig {
  const gtmId = normalizeId(settings.analytics_gtm_id)
  const ga4Id = normalizeId(settings.analytics_ga4_id)
  const consentDefault: ConsentChoice =
    settings.analytics_consent_default === 'granted' ? 'granted' : 'denied'

  return { gtmId, ga4Id, consentDefault, enabled: gtmId !== null || ga4Id !== null }
}

export function readConsentChoice(): ConsentChoice | null {
  try {
    const value = localStorage.getItem(CONSENT_STORAGE_KEY)

    return value === 'granted' || value === 'denied' ? value : null
  } catch {
    return null
  }
}

export function storeConsentChoice(choice: ConsentChoice): void {
  try {
    localStorage.setItem(CONSENT_STORAGE_KEY, choice)
  } catch {
    // Storage can be unavailable (private mode / disabled cookies) — non-fatal.
  }
}

/** Consent Mode v2 storage keys the banner toggles together. */
const CONSENT_KEYS = [
  'ad_storage',
  'ad_user_data',
  'ad_personalization',
  'analytics_storage',
  'functionality_storage',
  'personalization_storage',
] as const

/** Push a Consent Mode v2 `update` (works for both GA4-direct and GTM). */
export function applyConsent(choice: ConsentChoice): void {
  if (typeof window.gtag !== 'function') {
    return
  }

  window.gtag('consent', 'update', Object.fromEntries(CONSENT_KEYS.map((key) => [key, choice])))
}

export interface PageViewInfo {
  path: string
  location: string
  title: string
}

/**
 * Report a page view for an in-app navigation. GTM expects a dataLayer event
 * (wire a "page_view" custom-event trigger in the container); GA4-direct uses
 * the gtag event API.
 */
export function trackPageView(config: WebAnalyticsClientConfig, info: PageViewInfo): void {
  if (!config.enabled) {
    return
  }

  if (config.gtmId !== null) {
    window.dataLayer?.push({
      event: 'page_view',
      page_path: info.path,
      page_location: info.location,
      page_title: info.title,
    })

    return
  }

  window.gtag?.('event', 'page_view', {
    page_path: info.path,
    page_location: info.location,
    page_title: info.title,
  })
}
