import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { renderHook } from '@testing-library/react'
import type { ReactNode } from 'react'
import { useUpdateEntity } from './mutations'
import { toEntityId } from './ids'
import { entityKeys } from './query-keys'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { mswServer } from '@tests/msw/server'

function freshClient(): QueryClient {
  return new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  })
}

describe('entity mutations invalidate the public feeds', () => {
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

  it('useUpdateEntity invalidates latest / search / by-tag / by-date-range feeds', async () => {
    seedEntities([{ id: 5, entity_type_id: 1, is_deleted: false, deleted_at: null }])
    const queryClient = freshClient()
    // Public feeds live under entityKeys.all but outside lists().
    queryClient.setQueryData(entityKeys.latest(10), [])
    queryClient.setQueryData(entityKeys.search('q', 10), [])
    queryClient.setQueryData(entityKeys.byTag('news', 10), [])
    queryClient.setQueryData(entityKeys.byDateRange('2026-01-01', '2026-01-31', 10), [])

    const wrapper = ({ children }: { children: ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    )
    const { result } = renderHook(() => useUpdateEntity(), { wrapper })
    await result.current.mutateAsync({ id: toEntityId(5), entityTypeId: 1, status: 'published' })

    expect(queryClient.getQueryState(entityKeys.latest(10))?.isInvalidated).toBe(true)
    expect(queryClient.getQueryState(entityKeys.search('q', 10))?.isInvalidated).toBe(true)
    expect(queryClient.getQueryState(entityKeys.byTag('news', 10))?.isInvalidated).toBe(true)
    expect(
      queryClient.getQueryState(entityKeys.byDateRange('2026-01-01', '2026-01-31', 10))
        ?.isInvalidated,
    ).toBe(true)
  })
})
