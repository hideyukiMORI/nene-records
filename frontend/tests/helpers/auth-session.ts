import { authStore } from '@/entities/auth'

export function seedAdminSession(): void {
  authStore.setSession({
    expiresAt: new Date(Date.now() + 86_400_000).toISOString(),
    email: 'admin@example.com',
    role: 'admin',
  })
}

export function seedEditorSession(): void {
  authStore.setSession({
    expiresAt: new Date(Date.now() + 86_400_000).toISOString(),
    email: 'editor@example.com',
    role: 'editor',
  })
}

export function clearAuthSession(): void {
  authStore.clearSession()
}
