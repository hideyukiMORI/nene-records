import { cleanup, render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { I18nProvider } from '@/shared/i18n'
import { PublicRecordListView, type PublicRecordListViewProps } from './PublicRecordListView'
import type { PublicRecordListItem } from '../hooks/use-public-browse-entity-records-page'

function makeItems(count: number): PublicRecordListItem[] {
  return Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    label: `Record ${String(i + 1)}`,
    publicUrl: `/posts/${String(i + 1)}`,
    publishedLabel: '',
  }))
}

const baseProps: PublicRecordListViewProps = {
  entityTypeSlug: 'posts',
  entityTypeName: 'Posts',
  entityTypes: [],
  items: makeItems(3),
  total: 3,
  offset: 0,
  pageSize: 20,
  hasPreviousPage: false,
  hasNextPage: false,
  onPreviousPage: vi.fn(),
  onNextPage: vi.fn(),
  isLoading: false,
  isError: false,
  isUnknownType: false,
  errorTitle: null,
  onRetry: vi.fn(),
}

function renderView(props: Partial<PublicRecordListViewProps>, locale: 'en' | 'ja' = 'en') {
  localStorage.setItem('nene-locale', locale)
  return render(
    <MemoryRouter>
      <I18nProvider>
        <PublicRecordListView {...baseProps} {...props} />
      </I18nProvider>
    </MemoryRouter>,
  )
}

afterEach(cleanup)

describe('PublicRecordListView i18n', () => {
  it('selects the singular record-count key in English', () => {
    renderView({ items: makeItems(1), total: 1 }, 'en')
    expect(screen.getByText('1 record')).toBeInTheDocument()
  })

  it('selects the plural record-count key in English', () => {
    renderView({ items: makeItems(3), total: 3 }, 'en')
    expect(screen.getByText('3 records')).toBeInTheDocument()
  })

  it('renders the paginated range sub-line in English', () => {
    renderView({ items: makeItems(20), total: 50 }, 'en')
    expect(screen.getByText('50 records · showing 1–20')).toBeInTheDocument()
  })

  it('interpolates the count under ja', () => {
    renderView({ items: makeItems(3), total: 3 }, 'ja')
    expect(screen.getByText('3 件')).toBeInTheDocument()
  })

  it('interpolates the entity type into the empty state under ja', () => {
    renderView({ items: [], total: 0 }, 'ja')
    expect(screen.getByText('公開済みのpostsはまだありません')).toBeInTheDocument()
  })
})
