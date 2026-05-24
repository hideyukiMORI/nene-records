import { authStore } from './model'
import { hasCapability, isAdmin, type Capability, type UserRole } from './capabilities'

export function getCurrentRole(): UserRole | undefined {
  const role = authStore.getSession()?.role
  if (role === 'admin' || role === 'editor') {
    return role
  }

  return undefined
}

export function currentUserHasCapability(capability: Capability): boolean {
  return hasCapability(authStore.getSession()?.role, capability)
}

export function currentUserIsAdmin(): boolean {
  return isAdmin(authStore.getSession()?.role)
}
