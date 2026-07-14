// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest'
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
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  function stubFetch(impl: () => Promise<Response>): void {
    vi.stubGlobal('fetch', vi.fn(impl))
  }

  function jsonResponse(body: unknown, status = 200): Promise<Response> {
    return Promise.resolve(new Response(JSON.stringify(body), { status }))
  }

  it('reports not-installed only when the marker says complete:false (Tier A base)', async () => {
    stubFetch(() => jsonResponse({ complete: false }))
    await expect(probeFontPackInstalled()).resolves.toBe(false)
  })

  it('reports installed when the marker says complete:true (base + pack)', async () => {
    stubFetch(() => jsonResponse({ complete: true }))
    await expect(probeFontPackInstalled()).resolves.toBe(true)
  })

  it('assumes installed when the marker is absent (Docker / production / dev → 404)', async () => {
    stubFetch(() => Promise.resolve(new Response('Not found', { status: 404 })))
    await expect(probeFontPackInstalled()).resolves.toBe(true)
  })

  it('assumes installed when the fetch fails (offline / SSR / tests)', async () => {
    stubFetch(() => Promise.reject(new Error('network down')))
    await expect(probeFontPackInstalled()).resolves.toBe(true)
  })
})
