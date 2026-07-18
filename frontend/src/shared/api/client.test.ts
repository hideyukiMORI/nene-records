import { afterEach, describe, expect, it, vi } from 'vitest'
import { authStore } from '@/entities/auth/model'
import { apiClient, AppError } from './client'

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

function problemResponse(status: number, title: string): Response {
  return new Response(
    JSON.stringify({ type: 'about:blank', title, status, instance: '/api/v1/x' }),
    { status, headers: { 'Content-Type': 'application/problem+json' } },
  )
}

function stubFetch(response: Response): ReturnType<typeof vi.fn> {
  const spy = vi.fn().mockResolvedValue(response)
  vi.stubGlobal('fetch', spy)
  return spy
}

function lastRequest(spy: ReturnType<typeof vi.fn>): { url: string; init: RequestInit } {
  const call = spy.mock.calls.at(-1)
  return { url: call?.[0] as string, init: call?.[1] as RequestInit }
}

function storeSession(): void {
  authStore.setSession({
    expiresAt: new Date(Date.now() + 60_000).toISOString(),
    email: 'admin@example.test',
    role: 'admin',
    emailVerified: true,
  })
}

afterEach(() => {
  vi.unstubAllGlobals()
  localStorage.clear()
})

describe('apiClient request shape', () => {
  it('always sends the CSRF header and the session cookie (credentials: include)', async () => {
    const spy = stubFetch(jsonResponse({ ok: true }))
    await apiClient.get('/entities')

    const { url, init } = lastRequest(spy)
    expect(url).toBe('/entities')
    expect(init.credentials).toBe('include')
    expect((init.headers as Record<string, string>)['X-Requested-With']).toBe('fetch')
  })

  it('sets Content-Type only when a body is sent', async () => {
    const spy = stubFetch(jsonResponse({ ok: true }))
    await apiClient.get('/entities')
    expect(
      (lastRequest(spy).init.headers as Record<string, string>)['Content-Type'],
    ).toBeUndefined()

    stubFetch(jsonResponse({ ok: true }, 201))
    const postSpy = vi.mocked(fetch)
    await apiClient.post('/entities', { slug: 'a' })
    const { init } = lastRequest(postSpy as ReturnType<typeof vi.fn>)
    expect((init.headers as Record<string, string>)['Content-Type']).toBe('application/json')
    expect(init.body).toBe('{"slug":"a"}')
    expect(init.method).toBe('POST')
  })

  it('returns undefined for 204 responses', async () => {
    stubFetch(new Response(null, { status: 204 }))
    await expect(apiClient.delete('/entities/1')).resolves.toBeUndefined()
  })

  it('does not set Content-Type on uploads (the browser owns the multipart boundary)', async () => {
    const spy = stubFetch(jsonResponse({ id: 1 }))
    await apiClient.upload('/media', new FormData())

    const { init } = lastRequest(spy)
    const headers = init.headers as Record<string, string>
    expect(headers['X-Requested-With']).toBe('fetch')
    expect(headers['Content-Type']).toBeUndefined()
    expect(init.credentials).toBe('include')
  })
})

describe('apiClient error handling', () => {
  it('throws an AppError built from the problem-details body', async () => {
    stubFetch(problemResponse(422, 'Validation Failed'))
    const error = await apiClient.post('/entities', {}).catch((e: unknown) => e)

    expect(error).toBeInstanceOf(AppError)
    expect((error as AppError).status).toBe(422)
    expect((error as AppError).title).toBe('Validation Failed')
  })

  it('clears the stored session on 401 (fail-closed logout)', async () => {
    storeSession()
    stubFetch(problemResponse(401, 'Unauthorized'))

    await expect(apiClient.get('/entities')).rejects.toBeInstanceOf(AppError)
    expect(authStore.getSession()).toBeNull()
  })

  it('keeps the session on a failed login attempt (401 from /auth/login)', async () => {
    // ログイン失敗で既存セッションの表示状態まで壊さない（path 除外の仕様を pin）
    storeSession()
    stubFetch(problemResponse(401, 'Bad Credentials'))

    await expect(apiClient.post('/auth/login', { email: 'x' })).rejects.toBeInstanceOf(AppError)
    expect(authStore.getSession()).not.toBeNull()
  })
})
