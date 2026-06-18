import { describe, expect, it } from 'vitest'
import {
  buildThemeStylesheet,
  isSafeTokenValue,
  type RuntimeThemeManifest,
  swatchFromManifest,
} from './runtime-themes'

function manifest(over: Partial<RuntimeThemeManifest> = {}): RuntimeThemeManifest {
  return {
    id: 'midnight',
    name: 'Midnight',
    version: '1.0.0',
    tokens: {
      light: { 'color-surface': 'oklch(98% 0 0)', 'color-accent': '#3355ff' },
      dark: { 'color-surface': 'oklch(18% 0 0)' },
    },
    ...over,
  }
}

describe('buildThemeStylesheet', () => {
  it('emits scoped light + dark token declarations', () => {
    const css = buildThemeStylesheet('midnight', manifest())
    expect(css).toContain(".nene-public[data-theme='midnight']{")
    expect(css).toContain('--color-surface:oklch(98% 0 0);')
    expect(css).toContain('--color-accent:#3355ff;')
    expect(css).toContain(".nene-public[data-theme='midnight-dark']{")
    expect(css).toContain('--color-surface:oklch(18% 0 0);')
  })

  it('drops unsafe values and invalid keys (defence in depth)', () => {
    const css = buildThemeStylesheet(
      'midnight',
      manifest({
        tokens: {
          light: {
            'color-accent': 'red; } body{display:none}',
            'color-surface': 'url(https://evil.test/x)',
            Bad_Key: 'oklch(50% 0 0)',
            'color-ok': 'oklch(60% 0.1 150)',
          },
          dark: {},
        },
      }),
    )
    expect(css).not.toContain('display:none')
    expect(css).not.toContain('url(')
    expect(css).not.toContain('Bad_Key')
    expect(css).toContain('--color-ok:oklch(60% 0.1 150);')
  })

  it('returns empty string for an invalid theme key', () => {
    expect(buildThemeStylesheet('Bad Key', manifest())).toBe('')
  })
})

describe('isSafeTokenValue', () => {
  it('accepts CSS colour/length functions', () => {
    expect(isSafeTokenValue('oklch(60% 0.1 250)')).toBe(true)
    expect(isSafeTokenValue('color-mix(in oklch, #fff, #000 12%)')).toBe(true)
  })
  it('rejects break-out and external constructs', () => {
    expect(isSafeTokenValue('red;}')).toBe(false)
    expect(isSafeTokenValue('url(x)')).toBe(false)
    expect(isSafeTokenValue('</style>')).toBe(false)
    expect(isSafeTokenValue('')).toBe(false)
  })
})

describe('swatchFromManifest', () => {
  it('derives the picker swatch from light tokens with fallbacks', () => {
    const sw = swatchFromManifest(manifest())
    expect(sw.surface).toBe('oklch(98% 0 0)')
    expect(sw.accent).toBe('#3355ff')
    // color-surface-raised missing → fallback
    expect(sw.raised).toBe('#ffffff')
  })
})
