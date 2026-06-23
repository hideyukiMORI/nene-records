import { cleanup, render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { I18nProvider } from '@/shared/i18n'
import { PublicTagArchiveView, type PublicTagArchiveViewProps } from './PublicTagArchiveView'

const baseProps: PublicTagArchiveViewProps = {
  tagName: 'news',
  groups: [],
  total: 0,
  isLoading: false,
  isError: false,
  errorTitle: null,
  onRetry: vi.fn(),
}

function renderView(props: Partial<PublicTagArchiveViewProps>, locale: 'en' | 'ja' = 'en') {
  localStorage.setItem('nene-locale', locale)
  return render(
    <MemoryRouter>
      <I18nProvider>
        <PublicTagArchiveView {...baseProps} {...props} />
      </I18nProvider>
    </MemoryRouter>,
  )
}

afterEach(cleanup)

describe('PublicTagArchiveView i18n', () => {
  it('selects the singular sub-count key in English', () => {
    renderView({ tagName: 'news', total: 1 }, 'en')
    expect(screen.getByText('1 record tagged “news”.')).toBeInTheDocument()
  })

  it('selects the plural sub-count key in English', () => {
    renderView({ tagName: 'news', total: 2 }, 'en')
    expect(screen.getByText('2 records tagged “news”.')).toBeInTheDocument()
  })

  it('interpolates tag + count under ja', () => {
    renderView({ tagName: 'ニュース', total: 2 }, 'ja')
    expect(screen.getByText('「ニュース」が付いた記事 2 件')).toBeInTheDocument()
  })

  it('shows the tag-specific empty state under ja', () => {
    renderView({ tagName: 'ニュース', total: 0 }, 'ja')
    expect(screen.getByText('「ニュース」が付いた記事はありません')).toBeInTheDocument()
  })
})
