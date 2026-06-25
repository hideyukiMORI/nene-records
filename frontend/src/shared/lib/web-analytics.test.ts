import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  applyConsent,
  readConsentChoice,
  resolveWebAnalytics,
  storeConsentChoice,
  trackPageView,
  type WebAnalyticsClientConfig,
} from './web-analytics'

afterEach(() => {
  localStorage.clear()
  delete window.gtag
  delete window.dataLayer
  vi.restoreAllMocks()
})

describe('resolveWebAnalytics', () => {
  it('is disabled for an empty settings map', () => {
    const config = resolveWebAnalytics({})
    expect(config.enabled).toBe(false)
    expect(config.gtmId).toBeNull()
    expect(config.ga4Id).toBeNull()
    expect(config.consentDefault).toBe('denied')
  })

  it('reads a valid GTM id', () => {
    const config = resolveWebAnalytics({ analytics_gtm_id: 'GTM-ABC1234' })
    expect(config.enabled).toBe(true)
    expect(config.gtmId).toBe('GTM-ABC1234')
  })

  it('reads a valid GA4 id', () => {
    const config = resolveWebAnalytics({ analytics_ga4_id: 'G-XYZ987' })
    expect(config.ga4Id).toBe('G-XYZ987')
    expect(config.gtmId).toBeNull()
  })

  it.each(['', 'G ABC', "G-1';x", 'G-<script>', 'x'.repeat(41)])(
    'rejects the invalid id %p',
    (raw) => {
      const config = resolveWebAnalytics({ analytics_gtm_id: raw, analytics_ga4_id: raw })
      expect(config.enabled).toBe(false)
    },
  )

  it('normalizes the consent default to denied unless explicitly granted', () => {
    expect(resolveWebAnalytics({ analytics_consent_default: 'granted' }).consentDefault).toBe(
      'granted',
    )
    expect(resolveWebAnalytics({ analytics_consent_default: 'yes' }).consentDefault).toBe('denied')
  })
})

describe('consent storage', () => {
  it('round-trips a stored choice', () => {
    expect(readConsentChoice()).toBeNull()
    storeConsentChoice('granted')
    expect(readConsentChoice()).toBe('granted')
  })

  it('ignores an unrecognized stored value', () => {
    localStorage.setItem('nene-records:analytics-consent', 'maybe')
    expect(readConsentChoice()).toBeNull()
  })
})

describe('applyConsent', () => {
  it('pushes a Consent Mode v2 update via gtag', () => {
    const gtag = vi.fn()
    window.gtag = gtag
    applyConsent('granted')
    expect(gtag).toHaveBeenCalledWith(
      'consent',
      'update',
      expect.objectContaining({ analytics_storage: 'granted', ad_storage: 'granted' }),
    )
  })

  it('is a no-op when gtag is absent', () => {
    expect(() => {
      applyConsent('denied')
    }).not.toThrow()
  })
})

describe('trackPageView', () => {
  const info = { path: '/posts/1', location: 'https://x.test/posts/1', title: 'Post' }

  it('pushes a dataLayer event in GTM mode', () => {
    const dataLayer: unknown[] = []
    window.dataLayer = dataLayer
    const config: WebAnalyticsClientConfig = {
      gtmId: 'GTM-ABC1234',
      ga4Id: null,
      consentDefault: 'denied',
      enabled: true,
    }
    trackPageView(config, info)
    expect(dataLayer).toEqual([
      {
        event: 'page_view',
        page_path: '/posts/1',
        page_location: info.location,
        page_title: 'Post',
      },
    ])
  })

  it('uses the gtag event API in GA4-direct mode', () => {
    const gtag = vi.fn()
    window.gtag = gtag
    const config: WebAnalyticsClientConfig = {
      gtmId: null,
      ga4Id: 'G-XYZ987',
      consentDefault: 'denied',
      enabled: true,
    }
    trackPageView(config, info)
    expect(gtag).toHaveBeenCalledWith(
      'event',
      'page_view',
      expect.objectContaining({ page_path: '/posts/1' }),
    )
  })

  it('does nothing when analytics is disabled', () => {
    const gtag = vi.fn()
    window.gtag = gtag
    trackPageView({ gtmId: null, ga4Id: null, consentDefault: 'denied', enabled: false }, info)
    expect(gtag).not.toHaveBeenCalled()
  })
})
