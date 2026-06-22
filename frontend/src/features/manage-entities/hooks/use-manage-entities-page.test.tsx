import type { ReactNode } from 'react'
import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { QueryClientProvider } from '@tanstack/react-query'
import { act, renderHook, waitFor } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { useManageEntitiesPage } from './use-manage-entities-page'
import { I18nProvider } from '@/shared/i18n'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { mswServer } from '@tests/msw/server'
import { createTestQueryClient } from '@tests/render/render-with-providers'

// useManageEntitiesPage reads/writes the URL (?q=) so it needs a Router in the tree.
function wrapper({ children }: { children: ReactNode }) {
  const queryClient = createTestQueryClient()
  return (
    <MemoryRouter initialEntries={['/admin/article']}>
      <I18nProvider>
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
      </I18nProvider>
    </MemoryRouter>
  )
}

interface SeedEntity {
  id: number
  status?: 'draft' | 'published' | 'archived'
  slug?: string | null
}

function seed(list: SeedEntity[]): void {
  seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
  seedEntities(
    list.map((e) => ({
      id: e.id,
      entity_type_id: 1,
      status: e.status ?? 'draft',
      slug: e.slug ?? null,
      is_deleted: false,
      deleted_at: null,
    })),
  )
}

describe('useManageEntitiesPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityStore()
    resetEntityTypeStore()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('loads entities for the entity type', async () => {
    seed([{ id: 1 }, { id: 2 }])

    const { result } = renderHook(() => useManageEntitiesPage(1), { wrapper })

    await waitFor(() => {
      expect(result.current.total).toBe(2)
    })
    expect(result.current.items).toHaveLength(2)
  })

  it('filters by status', async () => {
    seed([
      { id: 1, status: 'draft' },
      { id: 2, status: 'published' },
    ])

    const { result } = renderHook(() => useManageEntitiesPage(1), { wrapper })
    await waitFor(() => {
      expect(result.current.total).toBe(2)
    })

    act(() => {
      result.current.setStatus('published')
    })

    await waitFor(() => {
      expect(result.current.total).toBe(1)
    })
    expect(result.current.items[0]?.id).toBe(2)
    expect(result.current.isFilterActive).toBe(true)
  })

  it('paginates when there are more than one page of records', async () => {
    seed(Array.from({ length: 25 }, (_, i) => ({ id: i + 1 })))

    const { result } = renderHook(() => useManageEntitiesPage(1), { wrapper })
    await waitFor(() => {
      expect(result.current.total).toBe(25)
    })

    expect(result.current.totalPages).toBe(2)
    expect(result.current.items).toHaveLength(20)

    act(() => {
      result.current.nextPage?.()
    })

    await waitFor(() => {
      expect(result.current.page).toBe(1)
      expect(result.current.items).toHaveLength(5)
    })
  })

  it('reflects the search query in hook state', async () => {
    seed([
      { id: 1, slug: 'alpha-post' },
      { id: 2, slug: 'beta-post' },
    ])

    const { result } = renderHook(() => useManageEntitiesPage(1), { wrapper })
    await waitFor(() => {
      expect(result.current.total).toBe(2)
    })

    act(() => {
      result.current.setSearchQuery('alpha')
    })

    await waitFor(() => {
      expect(result.current.searchQuery).toBe('alpha')
      expect(result.current.total).toBe(1)
    })
    expect(result.current.items[0]?.id).toBe(1)
  })
})
