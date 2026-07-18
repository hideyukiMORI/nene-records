import { describe, expect, it } from 'vitest'
import { hasCapability, isAdmin, isSuperadmin, type Capability } from './capabilities'

const ALL_CAPABILITIES: readonly Capability[] = [
  'manage_organizations',
  'manage_schema',
  'manage_settings',
  'read_settings',
  'manage_tags',
  'edit_content',
]

describe('hasCapability', () => {
  it('grants every capability to superadmin', () => {
    for (const capability of ALL_CAPABILITIES) {
      expect(hasCapability('superadmin', capability)).toBe(true)
    }
  })

  it('grants admin everything except manage_organizations', () => {
    expect(hasCapability('admin', 'manage_organizations')).toBe(false)
    for (const capability of ALL_CAPABILITIES.filter((c) => c !== 'manage_organizations')) {
      expect(hasCapability('admin', capability)).toBe(true)
    }
  })

  it('grants editor only read_settings and edit_content', () => {
    const granted = ALL_CAPABILITIES.filter((c) => hasCapability('editor', c))
    expect(granted).toEqual(['read_settings', 'edit_content'])
  })

  it('denies everything to an unknown or absent role', () => {
    for (const capability of ALL_CAPABILITIES) {
      expect(hasCapability(undefined, capability)).toBe(false)
      expect(hasCapability('', capability)).toBe(false)
      expect(hasCapability('viewer', capability)).toBe(false)
      // ロール名の正規化はしない仕様 — 大文字はそのまま拒否される
      expect(hasCapability('Admin', capability)).toBe(false)
    }
  })
})

describe('isAdmin / isSuperadmin', () => {
  it('treats admin and superadmin as admin', () => {
    expect(isAdmin('admin')).toBe(true)
    expect(isAdmin('superadmin')).toBe(true)
    expect(isAdmin('editor')).toBe(false)
    expect(isAdmin(undefined)).toBe(false)
  })

  it('treats only superadmin as superadmin', () => {
    expect(isSuperadmin('superadmin')).toBe(true)
    expect(isSuperadmin('admin')).toBe(false)
    expect(isSuperadmin(undefined)).toBe(false)
  })
})
