/**
 * Non-secret session profile kept in localStorage for UI gating (role-based
 * rendering, expiry checks). The actual session token lives in an HttpOnly
 * cookie set by the API and is never readable from JS.
 */
export interface AuthSession {
  expiresAt: string
  email: string
  role: string
}

const STORAGE_KEY = 'nene_records_session'

export const authStore = {
  getSession(): AuthSession | null {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    try {
      return JSON.parse(raw) as AuthSession
    } catch (e) {
      // Corrupted session JSON is intentionally treated as "no session" (never
      // honored as valid). In dev, surface it so unexpected logouts are debuggable.
      if (import.meta.env.DEV) {
        console.warn('[authStore] Failed to parse session from localStorage:', e)
      }
      return null
    }
  },

  setSession(session: AuthSession): void {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(session))
  },

  clearSession(): void {
    localStorage.removeItem(STORAGE_KEY)
  },

  isAuthenticated(): boolean {
    const session = this.getSession()
    if (!session) return false
    return new Date(session.expiresAt) > new Date()
  },
}
