import { cleanup, fireEvent, render, screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { I18nProvider } from '@/shared/i18n'
import type { WebAnalyticsClientConfig } from '@/shared/lib/web-analytics'
import { ConsentBanner } from './ConsentBanner'

afterEach(() => {
  cleanup()
  localStorage.clear()
  delete window.gtag
})

const enabledDenied: WebAnalyticsClientConfig = {
  gtmId: null,
  ga4Id: 'G-XYZ987',
  consentDefault: 'denied',
  enabled: true,
}

function renderBanner(config: WebAnalyticsClientConfig) {
  return render(
    <I18nProvider>
      <ConsentBanner config={config} />
    </I18nProvider>,
  )
}

describe('ConsentBanner', () => {
  it('prompts when analytics is enabled, default denied, and no prior choice', () => {
    renderBanner(enabledDenied)
    expect(screen.getByRole('dialog')).toBeTruthy()
    expect(screen.getByText('Allow')).toBeTruthy()
    expect(screen.getByText('Decline')).toBeTruthy()
  })

  it('grants consent and hides on Allow', () => {
    const gtag = vi.fn()
    window.gtag = gtag
    renderBanner(enabledDenied)

    fireEvent.click(screen.getByText('Allow'))

    expect(gtag).toHaveBeenCalledWith(
      'consent',
      'update',
      expect.objectContaining({ analytics_storage: 'granted' }),
    )
    expect(localStorage.getItem('nene-records:analytics-consent')).toBe('granted')
    expect(screen.queryByRole('dialog')).toBeNull()
  })

  it('records a denied choice on Decline', () => {
    const gtag = vi.fn()
    window.gtag = gtag
    renderBanner(enabledDenied)

    fireEvent.click(screen.getByText('Decline'))

    expect(localStorage.getItem('nene-records:analytics-consent')).toBe('denied')
    expect(screen.queryByRole('dialog')).toBeNull()
  })

  it('does not prompt when analytics is disabled', () => {
    renderBanner({ gtmId: null, ga4Id: null, consentDefault: 'denied', enabled: false })
    expect(screen.queryByRole('dialog')).toBeNull()
  })

  it('does not prompt when the default is granted', () => {
    renderBanner({ ...enabledDenied, consentDefault: 'granted' })
    expect(screen.queryByRole('dialog')).toBeNull()
  })

  it('does not prompt when a choice is already stored', () => {
    localStorage.setItem('nene-records:analytics-consent', 'denied')
    renderBanner(enabledDenied)
    expect(screen.queryByRole('dialog')).toBeNull()
  })
})
