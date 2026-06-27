import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { renderHook, waitFor } from '@testing-library/react'
import type { ReactNode } from 'react'
import { useDirectoryEntityList } from './queries'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { mswServer } from '@tests/msw/server'

function freshClient(): QueryClient {
  return new QueryClient({ defaultOptions: { queries: { retry: false } } })
}

describe('useDirectoryEntityList (#682)', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityStore()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('pages through every record (100 at a time) to build the complete directory set', async () => {
    seedEntities(
      Array.from({ length: 250 }, (_, i) => ({
        id: i + 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      })),
    )
    const client = freshClient()

    const { result } = renderHook(
      () =>
        useDirectoryEntityList({
          entityTypeId: 1,
          tagSlugs: [],
          relationFilters: {},
          limit: 20,
          offset: 0,
        }),
      {
        wrapper: ({ children }: { children: ReactNode }) => (
          <QueryClientProvider client={client}>{children}</QueryClientProvider>
        ),
      },
    )

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    // All 250 records arrive across 3 pages — no silent first-100 cut-off (#682).
    expect(result.current.data?.items).toHaveLength(250)
    expect(result.current.data?.total).toBe(250)
    expect(result.current.data?.truncated).toBe(false)
  })
})
