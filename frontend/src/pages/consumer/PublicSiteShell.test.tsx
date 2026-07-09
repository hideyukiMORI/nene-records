import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, within } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { MemoryRouter } from 'react-router-dom'
import { PublicSiteShell } from '@/pages/consumer/PublicSiteShell'
import type { PublicSite } from '@/pages/consumer/public-site-context'
import { DEFAULT_FOOTER_CONFIG } from '@/shared/lib/footer-config'
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
    footerConfig: DEFAULT_FOOTER_CONFIG,
    homeHero: '',
    frontPagePath: '',
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

  it('renders no brand mark when the tenant has no logo (#752)', () => {
    // ロゴ未設定時にプラットフォームのマーク（NeneMark）をフォールバック表示しない。
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    const { container } = renderShell(makeSite())

    expect(container.querySelector('.brand__mark')).toBeNull()
    expect(screen.getAllByText('Test Site').length).toBeGreaterThan(0)
  })

  it('renders the header-region menu widget items as the primary nav (#756)', async () => {
    // レイアウトビルダーで header 領域に置いたメニューウィジェットが公開ナビを駆動する。
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    mswServer.use(
      http.get('/api/v1/public/widgets', () =>
        HttpResponse.json({
          items: [
            {
              id: 4,
              widget_type: 'menu',
              region: 'header',
              display_order: 0,
              title: null,
              settings: { menuId: 1 },
              created_at: '2026-07-09 00:00:00',
              updated_at: '2026-07-09 00:00:00',
            },
          ],
        }),
      ),
    )

    renderShell(
      makeSite({
        navItems: [
          { id: 1, url: '/', label: 'HOME', menuId: 1 },
          { id: 2, url: '/services', label: 'SERVICES', menuId: 1 },
          { id: 3, url: '/other', label: 'OTHER', menuId: 2 },
        ],
      }),
    )

    // widgets 取得後に primary nav がメニュー項目へ置き換わる。
    expect((await screen.findAllByText('HOME')).length).toBeGreaterThan(0)
    const nav = screen.getByRole('navigation', { name: 'Primary' })
    expect(within(nav).getByText('HOME')).toBeInTheDocument()
    expect(within(nav).getByText('SERVICES')).toBeInTheDocument()
    // 他メニュー所属の項目・従来のフォールバックナビは primary nav に出ない
    //（Latest はフッターには正当に存在するため nav スコープで確認する）。
    expect(within(nav).queryByText('OTHER')).toBeNull()
    expect(within(nav).queryByText('Latest')).toBeNull()
  })

  it('renders footer-region menu widgets as footer columns (#758)', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    mswServer.use(
      http.get('/api/v1/public/widgets', () =>
        HttpResponse.json({
          items: [
            {
              id: 9,
              widget_type: 'menu',
              region: 'footer',
              display_order: 0,
              title: 'サイトマップ',
              settings: { menuId: 1 },
              created_at: '2026-07-09 00:00:00',
              updated_at: '2026-07-09 00:00:00',
            },
          ],
        }),
      ),
      http.get('/api/v1/public/menus', () =>
        HttpResponse.json({
          items: [
            {
              id: 1,
              name: 'FOOTER MENU',
              slug: 'footer-menu',
              location: null,
              created_at: '2026-07-09 00:00:00',
              updated_at: '2026-07-09 00:00:00',
            },
          ],
        }),
      ),
    )

    renderShell(
      makeSite({
        navItems: [{ id: 1, url: '/company', label: 'COMPANY', menuId: 1 }],
      }),
    )

    // widget title が列見出しになり、メニュー項目が列に並ぶ。既定の Browse 列は出ない。
    expect(await screen.findByText('サイトマップ')).toBeInTheDocument()
    expect(screen.getByText('COMPANY')).toBeInTheDocument()
    expect(screen.queryByText('Browse')).toBeNull()
  })

  it('renders footer_markdown as markdown (links work) (#762)', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    renderShell(
      makeSite({ footerMarkdown: '運営: AYANE — [免責事項](/disclaimer) をご確認ください。' }),
    )

    const link = await screen.findByRole('link', { name: '免責事項' })
    expect(link).toHaveAttribute('href', '/disclaimer')
  })

  it('renders footer_config: legal links, social icons, powered-by hidden (#766)', () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    renderShell(
      makeSite({
        footerConfig: {
          social: [
            { platform: 'x', url: 'https://x.com/ayane' },
            { platform: 'line', url: 'javascript:alert(1)' },
          ],
          legalLinks: [
            { label: 'プライバシーポリシー', url: '/privacy' },
            { label: '特商法表記', url: 'https://example.com/tokushoho' },
          ],
          showPoweredBy: false,
        },
      }),
    )

    expect(screen.getByRole('link', { name: 'プライバシーポリシー' })).toHaveAttribute(
      'href',
      '/privacy',
    )
    expect(screen.getByRole('link', { name: '特商法表記' })).toHaveAttribute(
      'href',
      'https://example.com/tokushoho',
    )
    expect(screen.getByRole('link', { name: 'x' })).toHaveAttribute('href', 'https://x.com/ayane')
    // 不正スキームの SNS リンクは落とす。Powered by は非表示。
    expect(screen.queryByRole('link', { name: 'line' })).toBeNull()
    expect(screen.queryByText(/Powered by NENE2/)).toBeNull()
  })

  it('keeps the powered-by note and no social row by default', () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    renderShell(makeSite())

    expect(screen.getByText(/Powered by NENE2/)).toBeInTheDocument()
    expect(document.querySelector('.ft__social')).toBeNull()
  })

  it('keeps the default footer columns when no footer menu widget exists', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    renderShell(makeSite())

    expect(await screen.findByText('Browse')).toBeInTheDocument()
    expect(screen.getByText('Content')).toBeInTheDocument()
  })

  it('keeps the default nav when no header menu widget exists', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    renderShell(makeSite())

    const nav = screen.getByRole('navigation', { name: 'Primary' })
    expect(await within(nav).findByText('Latest')).toBeInTheDocument()
  })

  it('renders the logo image in the brand mark when a logo is set', () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    const { container } = renderShell(makeSite({ logo: '/media/2026/07/logo.png' }))

    expect(container.querySelector('.brand__mark')).not.toBeNull()
    const img = container.querySelector('.brand__logo')
    expect(img).not.toBeNull()
    expect(img?.getAttribute('src')).toBe('/media/2026/07/logo.png')
  })
})
