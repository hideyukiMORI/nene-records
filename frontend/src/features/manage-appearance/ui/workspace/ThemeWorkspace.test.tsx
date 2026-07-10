import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest'
import { cleanup, fireEvent, screen } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { DEFAULT_FOOTER_CONFIG } from '@/shared/lib/footer-config'
import { DEFAULT_HEADER_CONFIG } from '@/shared/lib/header-config'
import { PUBLIC_THEMES } from '@/shared/lib/public-themes'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'
import type { FooterConfigPageState } from '../../hooks/useFooterConfigPage'
import type { HeaderConfigPageState } from '../../hooks/useHeaderConfigPage'
import type { HomeHeroPageState } from '../../hooks/useHomeHeroPage'
import type { PublicThemePageState } from '../../hooks/usePublicThemePage'
import type { ThemeCustomizePageState } from '../../hooks/useThemeCustomizePage'
import { ThemeWorkspace } from './ThemeWorkspace'

function makeStates(
  over: {
    customize?: Partial<ThemeCustomizePageState>
    header?: Partial<HeaderConfigPageState>
  } = {},
) {
  const pick: PublicThemePageState = {
    themes: PUBLIC_THEMES,
    activeThemeId: 'consumer',
    selectTheme: vi.fn(),
    isLoading: false,
    isSaving: false,
    pendingThemeId: null,
    runtimeThemes: [],
    runtimeKeys: new Set<string>(),
    deleteTheme: vi.fn(),
    updateTheme: vi.fn(),
    isMutating: false,
  }
  const customize: ThemeCustomizePageState = {
    themeId: 'consumer',
    draft: {},
    setKnob: vi.fn(),
    save: vi.fn(),
    reset: vi.fn(),
    canSaveAsTheme: true,
    saveAsNewTheme: vi.fn(),
    isCreating: false,
    isLoading: false,
    isSaving: false,
    isDirty: false,
    ...over.customize,
  }
  const header: HeaderConfigPageState = {
    draft: DEFAULT_HEADER_CONFIG,
    setTopbar: vi.fn(),
    setCta: vi.fn(),
    save: vi.fn(),
    isLoading: false,
    isSaving: false,
    isDirty: false,
    ...over.header,
  }
  const footer: FooterConfigPageState = {
    draft: DEFAULT_FOOTER_CONFIG,
    setSocial: vi.fn(),
    setLegalLinks: vi.fn(),
    setShowPoweredBy: vi.fn(),
    setCta: vi.fn(),
    setBanners: vi.fn(),
    save: vi.fn(),
    isLoading: false,
    isSaving: false,
    isDirty: false,
  }
  const hero: HomeHeroPageState = {
    draft: '',
    setDraft: vi.fn(),
    save: vi.fn(),
    isLoading: false,
    isSaving: false,
    isDirty: false,
  }
  return { pick, customize, header, footer, hero }
}

function renderWorkspace(states = makeStates()) {
  renderWithProviders(<ThemeWorkspace {...states} />)
  return states
}

describe('ThemeWorkspace (#787 IA redesign)', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    cleanup()
  })
  afterAll(() => {
    mswServer.close()
  })

  const stubQueries = () => {
    mswServer.use(
      http.get('/api/v1/media', () => HttpResponse.json({ items: [], limit: 100, offset: 0 })),
      http.get('/api/v1/themes/authoring-guide', () =>
        HttpResponse.json({ renderModel: { optionalTokens: {} } }),
      ),
    )
  }

  it('opens on the theme picker and switches panels via the nav', () => {
    stubQueries()
    renderWorkspace()

    // Theme picker panel is the default.
    expect(screen.getByRole('heading', { name: 'Choose a theme' })).toBeInTheDocument()

    // Switching to Brand shows its cards; the picker panel goes away.
    fireEvent.click(screen.getByRole('button', { name: /Brand/ }))
    expect(screen.getByRole('heading', { name: 'Colors' })).toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Choose a theme' })).toBeNull()
  })

  it('shows the fixed save bar only while the overrides draft is dirty', () => {
    stubQueries()
    const states = makeStates({ customize: { isDirty: true } })
    renderWorkspace(states)

    const bar = screen.getByRole('status')
    expect(bar.className).toContain('show')

    fireEvent.click(screen.getByRole('button', { name: 'Save changes' }))
    expect(states.customize.save).toHaveBeenCalled()
    fireEvent.click(screen.getByRole('button', { name: 'Reset' }))
    expect(states.customize.reset).toHaveBeenCalled()
  })

  it('keeps the save bar hidden when the draft is clean', () => {
    stubQueries()
    renderWorkspace()
    expect(screen.getByRole('status').className).not.toContain('show')
  })

  it('marks customize nav items dirty via the shared overrides draft', () => {
    stubQueries()
    renderWorkspace(makeStates({ customize: { isDirty: true } }))
    const brand = screen.getByRole('button', { name: /Brand/ })
    expect(brand.querySelector('.ws-dirty-dot.on')).not.toBeNull()
  })

  it('unifies the header section: content view + appearance disclosure', () => {
    stubQueries()
    renderWorkspace()
    fireEvent.click(screen.getByRole('button', { name: /^Header$/ }))

    // The header_config content form is present…
    expect(screen.getByText('Show Top bar')).toBeInTheDocument()
    // …and the theme_overrides appearance controls are behind a disclosure.
    const disclosure = screen.getByRole('button', { name: /Placement & look/ })
    expect(disclosure).toHaveAttribute('aria-expanded', 'false')
    fireEvent.click(disclosure)
    expect(screen.getByText('Header layout')).toBeInTheDocument()
  })
})
