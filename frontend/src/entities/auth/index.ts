export { authStore } from './model'
export type { AuthSession } from './model'
export { useLogin, useLogout } from './mutations'
export { hasCapability, isAdmin, isSuperadmin } from './capabilities'
export type { Capability, UserRole } from './capabilities'
export {
  currentUserHasCapability,
  currentUserIsAdmin,
  currentUserIsSuperadmin,
  getCurrentRole,
} from './authorization'
