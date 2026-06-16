import { describe, expect, it } from 'vitest'
import { DEFAULT_PUBLIC_THEME_ID, PUBLIC_THEMES, resolvePublicThemeId } from './public-themes'

describe('resolvePublicThemeId', () => {
  it('returns a known theme id unchanged', () => {
    expect(resolvePublicThemeId('consumer')).toBe('consumer')
  })

  it('falls back to the default for unknown / empty / nullish values', () => {
    expect(resolvePublicThemeId('does-not-exist')).toBe(DEFAULT_PUBLIC_THEME_ID)
    expect(resolvePublicThemeId('')).toBe(DEFAULT_PUBLIC_THEME_ID)
    expect(resolvePublicThemeId(null)).toBe(DEFAULT_PUBLIC_THEME_ID)
    expect(resolvePublicThemeId(undefined)).toBe(DEFAULT_PUBLIC_THEME_ID)
  })

  it('default theme id is registered', () => {
    expect(PUBLIC_THEMES.some((theme) => theme.id === DEFAULT_PUBLIC_THEME_ID)).toBe(true)
  })
})
