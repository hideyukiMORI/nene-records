export type UserRole = 'admin' | 'editor'

export type Capability =
  | 'manage_schema'
  | 'manage_settings'
  | 'read_settings'
  | 'manage_tags'
  | 'edit_content'

const EDITOR_CAPABILITIES: readonly Capability[] = ['read_settings', 'edit_content'] as const

export function hasCapability(role: string | undefined, capability: Capability): boolean {
  if (role === 'admin') {
    return true
  }

  if (role === 'editor') {
    return EDITOR_CAPABILITIES.includes(capability)
  }

  return false
}

export function isAdmin(role: string | undefined): boolean {
  return role === 'admin'
}
