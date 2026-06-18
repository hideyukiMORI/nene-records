import { cleanup, fireEvent, render, screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { I18nProvider } from '@/shared/i18n'
import { PUBLIC_THEMES } from '@/shared/lib/public-themes'
import { PublicThemeView } from './PublicThemeView'
import type { PublicThemePageState } from '../hooks/usePublicThemePage'

afterEach(cleanup)

function renderView(overrides: Partial<PublicThemePageState> = {}) {
  const selectTheme = vi.fn()
  const state: PublicThemePageState = {
    themes: PUBLIC_THEMES,
    activeThemeId: 'consumer',
    selectTheme,
    isLoading: false,
    isSaving: false,
    pendingThemeId: null,
    ...overrides,
  }
  render(
    <I18nProvider>
      <PublicThemeView {...state} />
    </I18nProvider>,
  )
  return { selectTheme }
}

describe('PublicThemeView', () => {
  it('renders a card per registered theme and marks the active one', () => {
    renderView()
    const active = screen.getByRole('button', { name: /Terracotta/ })
    expect(active).toHaveAttribute('aria-pressed', 'true')
  })

  it('calls selectTheme when a theme card is clicked', () => {
    const { selectTheme } = renderView({ activeThemeId: 'other' })
    fireEvent.click(screen.getByRole('button', { name: /Terracotta/ }))
    expect(selectTheme).toHaveBeenCalledWith('consumer')
  })

  it('disables cards while saving', () => {
    renderView({ isSaving: true })
    expect(screen.getByRole('button', { name: /Terracotta/ })).toBeDisabled()
  })

  it('renders a composed runtime theme card and selects it by key', () => {
    const runtime = {
      id: 'midnight',
      name: 'Midnight',
      description: 'Runtime theme.',
      author: 'Runtime',
      version: '1.0.0',
      createdAt: '2026-06-18',
      preview: { surface: '#101010', raised: '#181818', accent: '#3355ff' },
    }
    const { selectTheme } = renderView({
      themes: [...PUBLIC_THEMES, runtime],
      activeThemeId: 'consumer',
    })
    fireEvent.click(screen.getByRole('button', { name: /Midnight/ }))
    expect(selectTheme).toHaveBeenCalledWith('midnight')
  })
})
