import { describe, expect, it } from 'vitest'
import { layoutRegions, regionForLayout, resolveLayout } from './resolve-layout'

describe('resolveLayout', () => {
  it('prefers the per-entity override', () => {
    expect(resolveLayout('bare', 'full')).toBe('bare')
  })

  it('falls back to the type default when there is no override', () => {
    expect(resolveLayout(null, 'full')).toBe('full')
  })

  it('falls back to the global default when both are missing', () => {
    expect(resolveLayout(null, null)).toBe('standard')
    expect(resolveLayout(undefined, undefined)).toBe('standard')
  })

  it('ignores unknown values and falls through', () => {
    expect(resolveLayout('bogus', 'full')).toBe('full')
    expect(resolveLayout('bogus', 'nope')).toBe('standard')
  })
})

describe('layoutRegions', () => {
  it('returns the regions each layout renders', () => {
    expect(layoutRegions('standard')).toEqual(['main'])
    expect(layoutRegions('two-col')).toEqual(['main', 'sidebar'])
    expect(layoutRegions('three-col')).toEqual(['main', 'sidebar', 'aside'])
  })
})

describe('regionForLayout', () => {
  it('keeps a region the layout renders', () => {
    expect(regionForLayout('sidebar', 'two-col')).toBe('sidebar')
  })

  it('falls back to main when the layout does not render the region', () => {
    // two-col has no aside; a single-column layout has only main.
    expect(regionForLayout('aside', 'two-col')).toBe('main')
    expect(regionForLayout('sidebar', 'standard')).toBe('main')
    expect(regionForLayout(null, 'three-col')).toBe('main')
  })
})
