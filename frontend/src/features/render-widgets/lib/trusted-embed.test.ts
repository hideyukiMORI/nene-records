import { describe, expect, it } from 'vitest'
import { parseEmbedAllowlist, resolveTrustedEmbed } from './trusted-embed'

const ALLOWLIST = ['https://widgets.example.com']

function validSettings(): Record<string, unknown> {
  return {
    origin: 'https://widgets.example.com',
    src: 'https://widgets.example.com/form.js',
    integrity: 'sha384-abcDEF123+/=',
  }
}

describe('parseEmbedAllowlist', () => {
  it('parses a JSON array of https origins', () => {
    expect(
      parseEmbedAllowlist({
        embed_allowlist: '["https://widgets.example.com","https://a.example.org"]',
      }),
    ).toEqual(['https://widgets.example.com', 'https://a.example.org'])
  })

  it('drops non-https, wildcard, and malformed entries', () => {
    expect(
      parseEmbedAllowlist({
        embed_allowlist: '["http://x.example.com","https://*.example.com","not-a-url"]',
      }),
    ).toEqual([])
  })

  it('returns [] for empty / missing / invalid JSON', () => {
    expect(parseEmbedAllowlist({})).toEqual([])
    expect(parseEmbedAllowlist({ embed_allowlist: '' })).toEqual([])
    expect(parseEmbedAllowlist({ embed_allowlist: '{oops' })).toEqual([])
  })
})

describe('resolveTrustedEmbed', () => {
  it('resolves a well-formed, allowlisted embed', () => {
    expect(resolveTrustedEmbed(validSettings(), ALLOWLIST)).toEqual({
      origin: 'https://widgets.example.com',
      src: 'https://widgets.example.com/form.js',
      integrity: 'sha384-abcDEF123+/=',
      attributes: {},
    })
  })

  it('refuses an origin that is not on the allowlist', () => {
    expect(resolveTrustedEmbed(validSettings(), ['https://other.example.org'])).toBeNull()
  })

  it('refuses a cross-origin src', () => {
    expect(
      resolveTrustedEmbed({ ...validSettings(), src: 'https://evil.example.net/x.js' }, ALLOWLIST),
    ).toBeNull()
  })

  it('refuses a missing or malformed SRI', () => {
    const noSri = validSettings()
    delete noSri['integrity']
    expect(resolveTrustedEmbed(noSri, ALLOWLIST)).toBeNull()
    expect(resolveTrustedEmbed({ ...validSettings(), integrity: 'md5-x' }, ALLOWLIST)).toBeNull()
  })

  it('accepts data-* attributes and refuses others', () => {
    expect(
      resolveTrustedEmbed({ ...validSettings(), attributes: { 'data-id': '7' } }, ALLOWLIST),
    ).toEqual({
      origin: 'https://widgets.example.com',
      src: 'https://widgets.example.com/form.js',
      integrity: 'sha384-abcDEF123+/=',
      attributes: { 'data-id': '7' },
    })
    expect(
      resolveTrustedEmbed({ ...validSettings(), attributes: { onload: 'x' } }, ALLOWLIST),
    ).toBeNull()
  })
})
