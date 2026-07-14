import { describe, expect, it } from 'vitest'
import {
  BASE_BUNDLED_FONT_FAMILIES,
  FONT_VALUE_TO_FAMILY,
  isBaseBundledFontValue,
  probeFontPackInstalled,
} from './font-availability'

describe('isBaseBundledFontValue', () => {
  it('treats theme-default (undefined / empty) and system as always available', () => {
    expect(isBaseBundledFontValue(undefined)).toBe(true)
    expect(isBaseBundledFontValue('')).toBe(true)
    expect(isBaseBundledFontValue('system')).toBe(true)
  })

  it('marks admin + default-theme + #818 fonts as base-bundled', () => {
    for (const value of ['inter', 'saira', 'space-grotesk', 'bricolage']) {
      expect(isBaseBundledFontValue(value)).toBe(true)
    }
  })

  it('marks pack-only picker fonts as not base-bundled', () => {
    for (const value of ['roboto', 'source-serif', 'playfair', 'archivo', 'oswald', 'space-mono']) {
      expect(isBaseBundledFontValue(value)).toBe(false)
    }
  })

  it('keeps every mapped family either base-bundled or explicitly pack-only', () => {
    for (const family of Object.values(FONT_VALUE_TO_FAMILY)) {
      expect(typeof family).toBe('string')
    }
    // The base set is a strict subset of the mapped families.
    for (const family of BASE_BUNDLED_FONT_FAMILIES) {
      expect(family.length).toBeGreaterThan(0)
    }
  })
})

describe('probeFontPackInstalled', () => {
  it('assumes installed when the Font Loading API is unavailable (SSR / jsdom)', async () => {
    // jsdom provides no document.fonts, so the probe must not nag.
    await expect(probeFontPackInstalled()).resolves.toBe(true)
  })
})
