import { describe, expect, it } from 'vitest'
import { resolveLayout } from './resolve-layout'

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
