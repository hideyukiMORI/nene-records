import { afterEach, describe, expect, it } from 'vitest'
import { authStore, type AuthSession } from './model'

const STORAGE_KEY = 'nene_records_session'

function session(overrides: Partial<AuthSession> = {}): AuthSession {
  return {
    expiresAt: new Date(Date.now() + 60_000).toISOString(),
    email: 'admin@example.test',
    role: 'admin',
    emailVerified: true,
    ...overrides,
  }
}

afterEach(() => {
  localStorage.clear()
})

describe('authStore', () => {
  it('round-trips a session through localStorage', () => {
    const s = session()
    authStore.setSession(s)
    expect(authStore.getSession()).toEqual(s)
  })

  it('returns null when no session is stored', () => {
    expect(authStore.getSession()).toBeNull()
  })

  it('treats corrupted session JSON as no session, never as valid', () => {
    localStorage.setItem(STORAGE_KEY, '{not json')
    expect(authStore.getSession()).toBeNull()
    expect(authStore.isAuthenticated()).toBe(false)
  })

  it('clearSession removes the stored profile', () => {
    authStore.setSession(session())
    authStore.clearSession()
    expect(authStore.getSession()).toBeNull()
  })

  it('isAuthenticated is true only while expiresAt is in the future', () => {
    authStore.setSession(session())
    expect(authStore.isAuthenticated()).toBe(true)

    authStore.setSession(session({ expiresAt: new Date(Date.now() - 1_000).toISOString() }))
    expect(authStore.isAuthenticated()).toBe(false)

    authStore.clearSession()
    expect(authStore.isAuthenticated()).toBe(false)
  })
})
