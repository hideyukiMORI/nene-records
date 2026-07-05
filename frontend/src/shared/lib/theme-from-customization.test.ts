import { describe, expect, it } from 'vitest'
import {
  buildThemeManifestFromCustomization,
  slugifyThemeId,
  uniqueThemeId,
} from './theme-from-customization'

const BASE = {
  light: { 'color-surface': '#ffffff', 'color-accent': '#111111', 'color-text-primary': '#000000' },
  dark: { 'color-surface': '#0f0f0f', 'color-accent': '#eeeeee', 'color-text-primary': '#ffffff' },
}

describe('slugifyThemeId', () => {
  it('produces a valid id (^[a-z][a-z0-9-]{1,40}$)', () => {
    expect(slugifyThemeId('My Cool Theme!')).toBe('my-cool-theme')
    expect(slugifyThemeId('  Spaces  ')).toBe('spaces')
    expect(slugifyThemeId('123 start')).toBe('t-123-start')
    expect(slugifyThemeId('')).toBe('theme')
    expect(slugifyThemeId('x'.repeat(80)).length).toBeLessThanOrEqual(41)
  })
})

describe('uniqueThemeId', () => {
  it('avoids taken ids (built-ins + existing runtime keys)', () => {
    expect(uniqueThemeId('ocean', new Set())).toBe('ocean')
    expect(uniqueThemeId('aurora', new Set(['aurora']))).toBe('aurora-2')
    expect(uniqueThemeId('aurora', new Set(['aurora', 'aurora-2']))).toBe('aurora-3')
  })
})

describe('buildThemeManifestFromCustomization', () => {
  it('layers overrides over the base full token set, per mode', () => {
    const manifest = buildThemeManifestFromCustomization({
      id: 'sunset',
      name: 'Sunset',
      description: 'Warm',
      baseTokens: BASE,
      overrides: { accent: '#ff5500', flags: { feedLayout: 'magazine' } },
    })

    expect(manifest.id).toBe('sunset')
    expect(manifest.version).toBe('1.0.0')
    expect(manifest.supportsModes).toEqual(['light', 'dark'])
    // Base token preserved, accent overridden in BOTH modes.
    expect(manifest.tokens.light['color-surface']).toBe('#ffffff')
    expect(manifest.tokens.light['color-accent']).toBe('#ff5500')
    expect(manifest.tokens.dark['color-accent']).toBe('#ff5500')
    // accent-hover derived by the customizer mapping.
    expect(manifest.tokens.light['color-accent-hover']).toContain('color-mix')
    expect(manifest.flags).toEqual({ feedLayout: 'magazine' })
    expect(manifest.description).toBe('Warm')
  })

  it('applies per-mode surface colours only to their mode', () => {
    const manifest = buildThemeManifestFromCustomization({
      id: 'paper',
      name: 'Paper',
      baseTokens: BASE,
      overrides: { surface: { light: '#fafaf0', dark: '#101014' } },
    })
    expect(manifest.tokens.light['color-surface']).toBe('#fafaf0')
    expect(manifest.tokens.dark['color-surface']).toBe('#101014')
    expect(manifest.description).toBeUndefined()
    expect(manifest.flags).toBeUndefined()
  })

  it('captures image slots as media-id assets, dropping empty slots', () => {
    const manifest = buildThemeManifestFromCustomization({
      id: 'brand',
      name: 'Brand',
      baseTokens: BASE,
      overrides: { images: { hero: { light: 5 }, logo: { light: 7, dark: 8 }, background: {} } },
    })
    expect(manifest.assets).toEqual({ hero: { light: 5 }, logo: { light: 7, dark: 8 } })
  })

  it('omits assets when there are no images', () => {
    const manifest = buildThemeManifestFromCustomization({
      id: 'plain',
      name: 'Plain',
      baseTokens: BASE,
      overrides: { accent: '#123456' },
    })
    expect(manifest.assets).toBeUndefined()
  })
})
