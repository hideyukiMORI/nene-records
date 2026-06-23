import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { renderHook, waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import type { ReactNode } from 'react'
import { useLogin, useLogout } from './mutations'
import { mswServer } from '@tests/msw/server'

function makeWrapper(queryClient: QueryClient) {
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  }
}

function freshClient(): QueryClient {
  return new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  })
}

describe('auth mutations clear the org-scoped query cache', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('useLogout wipes cached data so the next session cannot read it', async () => {
    mswServer.use(http.post('/api/v1/auth/logout', () => new HttpResponse(null, { status: 204 })))
    const queryClient = freshClient()
    queryClient.setQueryData(['entities', 'list'], { items: [{ id: 1 }] })

    const { result } = renderHook(() => useLogout(), { wrapper: makeWrapper(queryClient) })
    await result.current.mutateAsync()

    await waitFor(() => {
      expect(queryClient.getQueryData(['entities', 'list'])).toBeUndefined()
    })
  })

  it('useLogin wipes any data cached before the session starts', async () => {
    mswServer.use(
      http.post('/api/v1/auth/login', () =>
        HttpResponse.json({ expires_at: null, email: 'a@b.co', role: 'admin' }),
      ),
    )
    const queryClient = freshClient()
    queryClient.setQueryData(['entities', 'list'], { items: [{ id: 1 }] })

    const { result } = renderHook(() => useLogin(), { wrapper: makeWrapper(queryClient) })
    await result.current.mutateAsync({ email: 'a@b.co', password: 'pw' })

    await waitFor(() => {
      expect(queryClient.getQueryData(['entities', 'list'])).toBeUndefined()
    })
  })
})
