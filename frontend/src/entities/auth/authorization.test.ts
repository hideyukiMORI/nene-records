import { afterEach, describe, expect, it } from 'vitest'
import {
  currentUserHasCapability,
  currentUserIsAdmin,
  currentUserIsSuperadmin,
  getCurrentRole,
} from './authorization'
import { authStore } from './model'

function storeRole(role: string): void {
  authStore.setSession({
    expiresAt: new Date(Date.now() + 60_000).toISOString(),
    email: 'user@example.test',
    role,
    emailVerified: true,
  })
}

afterEach(() => {
  localStorage.clear()
})

describe('getCurrentRole', () => {
  it('returns the stored role for the three known roles', () => {
    for (const role of ['superadmin', 'admin', 'editor'] as const) {
      storeRole(role)
      expect(getCurrentRole()).toBe(role)
    }
  })

  it('returns undefined for an unknown role or missing session', () => {
    expect(getCurrentRole()).toBeUndefined()
    // サーバが新ロールを返しても UI 側は未知として扱う（勝手に昇格しない）
    storeRole('owner')
    expect(getCurrentRole()).toBeUndefined()
  })
})

describe('currentUser* helpers', () => {
  it('answers capabilities from the stored session role', () => {
    storeRole('editor')
    expect(currentUserHasCapability('edit_content')).toBe(true)
    expect(currentUserHasCapability('manage_schema')).toBe(false)
    expect(currentUserIsAdmin()).toBe(false)

    storeRole('admin')
    expect(currentUserHasCapability('manage_schema')).toBe(true)
    expect(currentUserHasCapability('manage_organizations')).toBe(false)
    expect(currentUserIsAdmin()).toBe(true)
    expect(currentUserIsSuperadmin()).toBe(false)

    storeRole('superadmin')
    expect(currentUserHasCapability('manage_organizations')).toBe(true)
    expect(currentUserIsSuperadmin()).toBe(true)
  })

  it('denies everything without a session', () => {
    expect(currentUserHasCapability('edit_content')).toBe(false)
    expect(currentUserIsAdmin()).toBe(false)
    expect(currentUserIsSuperadmin()).toBe(false)
  })
})
