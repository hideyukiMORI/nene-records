import { describe, expect, it } from 'vitest'
import { moveItem, reorderItem } from './move-item'

describe('moveItem', () => {
  it('moves an item up', () => {
    expect(moveItem(['a', 'b', 'c'], 1, -1)).toEqual(['b', 'a', 'c'])
  })

  it('moves an item down', () => {
    expect(moveItem(['a', 'b', 'c'], 1, 1)).toEqual(['a', 'c', 'b'])
  })

  it('returns null at the top edge', () => {
    expect(moveItem(['a', 'b', 'c'], 0, -1)).toBeNull()
  })

  it('returns null at the bottom edge', () => {
    expect(moveItem(['a', 'b', 'c'], 2, 1)).toBeNull()
  })

  it('returns null for an out-of-range source index (e.g. failed indexOf)', () => {
    expect(moveItem(['a', 'b', 'c'], -1, 1)).toBeNull()
    expect(moveItem(['a', 'b', 'c'], 5, -1)).toBeNull()
  })

  it('does not mutate the input', () => {
    const input = ['a', 'b', 'c']
    moveItem(input, 1, -1)
    expect(input).toEqual(['a', 'b', 'c'])
  })
})

describe('reorderItem', () => {
  it('moves an earlier item to a later drop index', () => {
    // drop index measured against original array: move 'a' to before the 4th slot
    expect(reorderItem(['a', 'b', 'c'], 0, 2)).toEqual(['b', 'a', 'c'])
  })

  it('moves a later item to an earlier drop index', () => {
    expect(reorderItem(['a', 'b', 'c'], 2, 0)).toEqual(['c', 'a', 'b'])
  })

  it('moving to the end appends', () => {
    expect(reorderItem(['a', 'b', 'c'], 0, 3)).toEqual(['b', 'c', 'a'])
  })

  it('returns null when fromIndex is out of range', () => {
    expect(reorderItem(['a', 'b'], -1, 0)).toBeNull()
  })

  it('does not mutate the input', () => {
    const input = ['a', 'b', 'c']
    reorderItem(input, 0, 2)
    expect(input).toEqual(['a', 'b', 'c'])
  })
})
