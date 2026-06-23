import { cleanup, render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { I18nProvider } from '@/shared/i18n'
import { PublicDateArchiveView, type PublicDateArchiveViewProps } from './PublicDateArchiveView'

const baseProps: PublicDateArchiveViewProps = {
  title: 'Archive: 2026/06',
  valid: true,
  groups: [],
  total: 0,
  isLoading: false,
  isError: false,
  errorTitle: null,
  onRetry: vi.fn(),
}

function renderView(props: Partial<PublicDateArchiveViewProps>, locale: 'en' | 'ja' = 'en') {
  localStorage.setItem('nene-locale', locale)
  return render(
    <MemoryRouter>
      <I18nProvider>
        <PublicDateArchiveView {...baseProps} {...props} />
      </I18nProvider>
    </MemoryRouter>,
  )
}

afterEach(cleanup)

describe('PublicDateArchiveView i18n', () => {
  it('selects the singular sub-count key in English', () => {
    renderView({ total: 1 }, 'en')
    expect(screen.getByText('1 record published in this period.')).toBeInTheDocument()
  })

  it('selects the plural sub-count key in English', () => {
    renderView({ total: 2 }, 'en')
    expect(screen.getByText('2 records published in this period.')).toBeInTheDocument()
  })

  it('interpolates count under ja', () => {
    renderView({ total: 2 }, 'ja')
    expect(screen.getByText('この期間に公開された記事 2 件')).toBeInTheDocument()
  })

  it('translates the invalid-date state under ja', () => {
    renderView({ valid: false }, 'ja')
    expect(screen.getByText('無効な日付')).toBeInTheDocument()
  })
})
