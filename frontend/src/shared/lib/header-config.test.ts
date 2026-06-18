import { describe, expect, it } from 'vitest'
import {
  DEFAULT_HEADER_CONFIG,
  hasCta,
  hasTopbarContent,
  parseHeaderConfig,
  safeHref,
  serializeHeaderConfig,
} from './header-config'

describe('parseHeaderConfig', () => {
  it('returns defaults for empty / invalid input', () => {
    expect(parseHeaderConfig(undefined)).toEqual(DEFAULT_HEADER_CONFIG)
    expect(parseHeaderConfig('')).toEqual(DEFAULT_HEADER_CONFIG)
    expect(parseHeaderConfig('not json')).toEqual(DEFAULT_HEADER_CONFIG)
    expect(parseHeaderConfig('[]')).toEqual(DEFAULT_HEADER_CONFIG)
  })

  it('coerces fields defensively (missing keys, wrong types)', () => {
    const parsed = parseHeaderConfig(
      JSON.stringify({ topbar: { enabled: true, phone: 123, email: 'a@b.co' }, cta: 'nope' }),
    )
    expect(parsed.topbar.enabled).toBe(true)
    expect(parsed.topbar.phone).toBe('') // number coerced to ''
    expect(parsed.topbar.email).toBe('a@b.co')
    expect(parsed.cta).toEqual({ enabled: false, label: '', url: '' })
  })

  it('round-trips via serialize', () => {
    const config = {
      topbar: { enabled: true, phone: '03-1', email: '', infoText: '9-5' },
      cta: { enabled: true, label: 'Buy', url: '/shop' },
    }
    expect(parseHeaderConfig(serializeHeaderConfig(config))).toEqual(config)
  })
})

describe('safeHref', () => {
  it('passes safe schemes and relative links', () => {
    expect(safeHref('https://example.com')).toBe('https://example.com')
    expect(safeHref('mailto:a@b.co')).toBe('mailto:a@b.co')
    expect(safeHref('tel:+81312345678')).toBe('tel:+81312345678')
    expect(safeHref('/contact')).toBe('/contact')
    expect(safeHref('#section')).toBe('#section')
  })

  it('upgrades a bare host to https', () => {
    expect(safeHref('example.com/x')).toBe('https://example.com/x')
  })

  it('blocks script-bearing schemes', () => {
    expect(safeHref('javascript:alert(1)')).toBe('')
    expect(safeHref('JavaScript:alert(1)')).toBe('')
    expect(safeHref('data:text/html,<script>')).toBe('')
    expect(safeHref('vbscript:msgbox')).toBe('')
  })
})

describe('hasTopbarContent / hasCta', () => {
  it('requires enabled + content for the Top bar', () => {
    expect(hasTopbarContent({ enabled: false, phone: '03', email: '', infoText: '' })).toBe(false)
    expect(hasTopbarContent({ enabled: true, phone: '', email: '', infoText: '' })).toBe(false)
    expect(hasTopbarContent({ enabled: true, phone: '03', email: '', infoText: '' })).toBe(true)
  })

  it('requires enabled + label + safe url for the CTA', () => {
    expect(hasCta({ enabled: true, label: 'Go', url: '/x' })).toBe(true)
    expect(hasCta({ enabled: false, label: 'Go', url: '/x' })).toBe(false)
    expect(hasCta({ enabled: true, label: '', url: '/x' })).toBe(false)
    expect(hasCta({ enabled: true, label: 'Go', url: 'javascript:alert(1)' })).toBe(false)
  })
})
