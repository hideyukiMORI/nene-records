import { describe, expect, it } from 'vitest'
import {
  canDropInto,
  type DirectoryDragPayload,
  dropPosition,
  moveInOrder,
  moveTargetPermalink,
  parentPath,
  reorderByInsert,
} from './directory-dnd'

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

describe('moveInOrder (#659)', () => {
  it('moves an item up and down among its siblings', () => {
    expect(moveInOrder([1, 2, 3], 1, -1)).toEqual([2, 1, 3])
    expect(moveInOrder([1, 2, 3], 1, 1)).toEqual([1, 3, 2])
  })

  it('returns the input unchanged for out-of-range moves', () => {
    const ids = [1, 2, 3]
    expect(moveInOrder(ids, 0, -1)).toBe(ids)
    expect(moveInOrder(ids, 2, 1)).toBe(ids)
  })
})

describe('drop-between reorder (#675)', () => {
  it('computes the parent path', () => {
    expect(parentPath('/company/about')).toBe('/company')
    expect(parentPath('/about')).toBe('')
  })

  it('reads the drop position from the pointer Y within a row', () => {
    expect(dropPosition(2, 0, 20)).toBe('before') // top 10%
    expect(dropPosition(10, 0, 20)).toBe('middle') // 50%
    expect(dropPosition(18, 0, 20)).toBe('after') // bottom 90%
  })

  it('reorders by inserting before/after a target sibling', () => {
    expect(reorderByInsert([1, 2, 3], 1, 2, 'after')).toEqual([2, 1, 3])
    expect(reorderByInsert([1, 2, 3], 3, 1, 'before')).toEqual([3, 1, 2])
  })
})
