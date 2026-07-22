import { describe, expect, it } from 'vitest'
import {
  DEFAULT_FLOATING_CTA,
  isFloatingCtaRenderable,
  joinList,
  parseFloatingCta,
  parseList,
  safeHref,
  serializeFloatingCta,
} from './floating-cta'

describe('parseFloatingCta', () => {
  it('returns the default for empty / invalid input', () => {
    expect(parseFloatingCta(undefined)).toEqual(DEFAULT_FLOATING_CTA)
    expect(parseFloatingCta('')).toEqual(DEFAULT_FLOATING_CTA)
    expect(parseFloatingCta('not json')).toEqual(DEFAULT_FLOATING_CTA)
    expect(parseFloatingCta('[]')).toEqual(DEFAULT_FLOATING_CTA)
  })

  it('parses a full config defensively', () => {
    const cfg = parseFloatingCta(
      JSON.stringify({
        enabled: true,
        position: 'bl',
        accent: '#D64525',
        content: { icon: '📅', iconId: 'calendar', label: 'Book', sub: 'Online' },
        link: { url: 'https://x.test', newTab: false },
        conditions: { types: ['page'], urlGlobs: ['/services*'], exclude: ['/admin*'] },
      }),
    )
    expect(cfg.enabled).toBe(true)
    expect(cfg.position).toBe('bl')
    expect(cfg.accent).toBe('#D64525')
    expect(cfg.content.label).toBe('Book')
    expect(cfg.content.iconId).toBe('calendar')
    expect(cfg.link.newTab).toBe(false)
    expect(cfg.conditions.urlGlobs).toEqual(['/services*'])
  })

  it('drops an invalid accent and coerces an unknown position', () => {
    const cfg = parseFloatingCta(JSON.stringify({ accent: 'red', position: 'top' }))
    expect(cfg.accent).toBe('')
    expect(cfg.position).toBe('br')
  })

  it('parses and clamps bottomOffset (#982 P2 c)', () => {
    expect(parseFloatingCta(JSON.stringify({ bottomOffset: 120 })).bottomOffset).toBe(120)
    expect(parseFloatingCta(JSON.stringify({ bottomOffset: 9999 })).bottomOffset).toBe(400)
    expect(parseFloatingCta(JSON.stringify({ bottomOffset: -5 })).bottomOffset).toBe(0)
    expect(parseFloatingCta(JSON.stringify({ bottomOffset: '80' })).bottomOffset).toBe(0)
    expect(parseFloatingCta(JSON.stringify({ bottomOffset: 12.5 })).bottomOffset).toBe(0)
    expect(parseFloatingCta('{}').bottomOffset).toBe(0)
  })

  it('round-trips through serialize', () => {
    const cfg = parseFloatingCta(
      JSON.stringify({ enabled: true, content: { label: 'x' }, link: { url: '/c' } }),
    )
    expect(parseFloatingCta(serializeFloatingCta(cfg))).toEqual(cfg)
  })
})

describe('safeHref (shared allowlist)', () => {
  it('rejects script-bearing schemes', () => {
    expect(safeHref('javascript:alert(1)')).toBe('')
    expect(safeHref('data:text/html,x')).toBe('')
  })
  it('accepts http(s)/mailto/tel and relative', () => {
    expect(safeHref('https://x.test')).toBe('https://x.test')
    expect(safeHref('mailto:a@b.test')).toBe('mailto:a@b.test')
    expect(safeHref('tel:+81312345678')).toBe('tel:+81312345678')
    expect(safeHref('/contact')).toBe('/contact')
  })
})

describe('list helpers', () => {
  it('parses and joins comma / newline lists', () => {
    expect(parseList(' page, post \n , ')).toEqual(['page', 'post'])
    expect(joinList(['a', 'b'])).toBe('a, b')
  })
})

describe('isFloatingCtaRenderable', () => {
  it('is true only for an enabled, labelled, safely-linked config', () => {
    expect(isFloatingCtaRenderable(DEFAULT_FLOATING_CTA)).toBe(false)
    expect(
      isFloatingCtaRenderable({
        ...DEFAULT_FLOATING_CTA,
        enabled: true,
        content: { icon: '', iconId: '', label: 'Book', sub: '' },
        link: { url: 'https://x.test', newTab: true },
      }),
    ).toBe(true)
    expect(
      isFloatingCtaRenderable({
        ...DEFAULT_FLOATING_CTA,
        enabled: true,
        content: { icon: '', iconId: '', label: 'Book', sub: '' },
        link: { url: 'javascript:alert(1)', newTab: true },
      }),
    ).toBe(false)
  })
})
