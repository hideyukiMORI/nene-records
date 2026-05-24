export interface AuthSession {
  token: string
  expiresAt: string
  email: string
  role: string
}

const STORAGE_KEY = 'nene_records_token'

export const authStore = {
  getSession(): AuthSession | null {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    try {
      return JSON.parse(raw) as AuthSession
    } catch {
      return null
    }
  },

  setSession(session: AuthSession): void {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(session))
  },

  clearSession(): void {
    localStorage.removeItem(STORAGE_KEY)
  },

  getToken(): string | null {
    return this.getSession()?.token ?? null
  },

  isAuthenticated(): boolean {
    const session = this.getSession()
    if (!session) return false
    return new Date(session.expiresAt) > new Date()
  },
}
