import { describe, expect, it } from 'vitest'
import { canDropInto, type DirectoryDragPayload, moveTargetPermalink } from './directory-dnd'

const payload: DirectoryDragPayload = { id: 1, permalink: '/company/about', label: 'About Us' }

describe('directory-dnd', () => {
  it('computes the target permalink under a new parent', () => {
    expect(moveTargetPermalink(payload, '/legal')).toBe('/legal/about')
    expect(moveTargetPermalink(payload, '/docs/guides')).toBe('/docs/guides/about')
  })

  it('allows dropping into an unrelated folder', () => {
    expect(canDropInto(payload, '/legal')).toBe(true)
  })

  it('rejects dropping onto itself', () => {
    expect(canDropInto(payload, '/company/about')).toBe(false)
  })

  it('rejects dropping into its own descendant (would cycle)', () => {
    expect(canDropInto(payload, '/company/about/team')).toBe(false)
  })

  it('rejects a no-op drop onto its current parent', () => {
    // Already at /company/about → dropping onto /company keeps the same permalink.
    expect(canDropInto(payload, '/company')).toBe(false)
  })
})
