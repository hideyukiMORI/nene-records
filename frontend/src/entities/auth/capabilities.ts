export type UserRole = 'superadmin' | 'admin' | 'editor'

export type Capability =
  | 'manage_organizations'
  | 'manage_schema'
  | 'manage_settings'
  | 'read_settings'
  | 'manage_tags'
  | 'edit_content'

const EDITOR_CAPABILITIES: readonly Capability[] = ['read_settings', 'edit_content'] as const

export function hasCapability(role: string | undefined, capability: Capability): boolean {
  if (role === 'superadmin') {
    return true
  }

  if (role === 'admin') {
    return capability !== 'manage_organizations'
  }

  if (role === 'editor') {
    return EDITOR_CAPABILITIES.includes(capability)
  }

  return false
}

export function isAdmin(role: string | undefined): boolean {
  return role === 'admin' || role === 'superadmin'
}

export function isSuperadmin(role: string | undefined): boolean {
  return role === 'superadmin'
}
