import { cleanup, render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { I18nProvider } from '@/shared/i18n'
import { PublicSearchView, type PublicSearchViewProps } from './PublicSearchView'

const baseProps: PublicSearchViewProps = {
  query: '',
  hasQuery: true,
  groups: [],
  total: 0,
  isLoading: false,
  isError: false,
  errorTitle: null,
  onSearch: vi.fn(),
  onRetry: vi.fn(),
}

function renderView(props: Partial<PublicSearchViewProps>, locale: 'en' | 'ja' = 'en') {
  localStorage.setItem('nene-locale', locale)
  return render(
    <MemoryRouter>
      <I18nProvider>
        <PublicSearchView {...baseProps} {...props} />
      </I18nProvider>
    </MemoryRouter>,
  )
}

afterEach(cleanup)

describe('PublicSearchView i18n', () => {
  it('selects the singular result-count key in English', () => {
    renderView({ query: 'cats', total: 1 }, 'en')
    expect(screen.getByText('1 result for “cats”')).toBeInTheDocument()
  })

  it('selects the plural result-count key in English', () => {
    renderView({ query: 'cats', total: 3 }, 'en')
    expect(screen.getByText('3 results for “cats”')).toBeInTheDocument()
  })

  it('interpolates query + count and translates the title under ja', () => {
    renderView({ query: '猫', total: 3 }, 'ja')
    expect(screen.getByText('「猫」の結果 3 件')).toBeInTheDocument()
    expect(screen.getByRole('heading', { level: 1, name: '検索' })).toBeInTheDocument()
  })

  it('shows the query-specific empty state under ja', () => {
    renderView({ query: '猫', total: 0 }, 'ja')
    expect(screen.getByText('「猫」に一致する結果はありません')).toBeInTheDocument()
  })
})
