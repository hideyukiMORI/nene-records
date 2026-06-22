import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { PublicSiteShell } from '@/pages/consumer/PublicSiteShell'
import type { PublicSite } from '@/pages/consumer/public-site-context'
import { DEFAULT_HEADER_CONFIG } from '@/shared/lib/header-config'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function makeSite(over: Partial<PublicSite> = {}): PublicSite {
  return {
    siteName: 'Test Site',
    tagline: '',
    metaDescription: '',
    footerMarkdown: '',
    logo: '',
    copyrightText: '',
    homeLayout: { columns: 2, mainPos: 'left', swap: false },
    navItems: [],
    activeTheme: 'consumer',
    themeOverrideCss: '',
    runtimeThemeCss: '',
    themeFlagAttrs: {},
    headerConfig: DEFAULT_HEADER_CONFIG,
    homeHero: '',
    ...over,
  }
}

function renderShell(site: PublicSite) {
  return renderWithProviders(
    <MemoryRouter initialEntries={['/']}>
      <PublicSiteShell site={site}>
        <p>body</p>
      </PublicSiteShell>
    </MemoryRouter>,
  )
}

describe('PublicSiteShell header reflection', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    cleanup()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('renders the Top bar and CTA when header_config has content', () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    renderShell(
      makeSite({
        headerConfig: {
          topbar: {
            enabled: true,
            phone: '03-1234-5678',
            email: 'info@example.com',
            infoText: 'Mon–Fri 9–18',
          },
          cta: { enabled: true, label: 'Contact us', url: '/contact' },
        },
      }),
    )

    expect(screen.getByText('Mon–Fri 9–18')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: '03-1234-5678' })).toHaveAttribute(
      'href',
      'tel:03-1234-5678',
    )
    const cta = screen.getByRole('link', { name: 'Contact us' })
    expect(cta).toHaveAttribute('href', '/contact')
    expect(cta).toHaveClass('hd__cta')
  })

  it('omits the Top bar / CTA when enabled but empty (no silent empty bar)', () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    renderShell(
      makeSite({
        headerConfig: {
          topbar: { enabled: true, phone: '', email: '', infoText: '' },
          cta: { enabled: true, label: '', url: '' },
        },
      }),
    )

    expect(document.querySelector('.hd-topbar')).toBeNull()
    expect(document.querySelector('.hd__cta')).toBeNull()
  })

  it('drops a CTA with an unsafe URL', () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    renderShell(
      makeSite({
        headerConfig: {
          ...DEFAULT_HEADER_CONFIG,
          cta: { enabled: true, label: 'Bad', url: 'javascript:alert(1)' },
        },
      }),
    )

    expect(screen.queryByRole('link', { name: 'Bad' })).toBeNull()
  })
})
