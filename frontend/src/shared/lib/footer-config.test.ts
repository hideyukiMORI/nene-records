import { describe, expect, it } from 'vitest'
import { DEFAULT_FOOTER_CONFIG, parseFooterConfig, serializeFooterConfig } from './footer-config'

describe('parseFooterConfig', () => {
  it('returns the default for empty / missing / broken JSON', () => {
    expect(parseFooterConfig(undefined)).toEqual(DEFAULT_FOOTER_CONFIG)
    expect(parseFooterConfig('')).toEqual(DEFAULT_FOOTER_CONFIG)
    expect(parseFooterConfig('not json')).toEqual(DEFAULT_FOOTER_CONFIG)
    expect(parseFooterConfig('[]')).toEqual(DEFAULT_FOOTER_CONFIG)
  })

  it('keeps only allowlisted platforms and complete entries', () => {
    const config = parseFooterConfig(
      JSON.stringify({
        social: [
          { platform: 'x', url: 'https://x.com/ayane' },
          { platform: 'myspace', url: 'https://example.com' },
          { platform: 'instagram', url: '' },
          'garbage',
        ],
        legalLinks: [
          { label: 'プライバシーポリシー', url: '/privacy' },
          { label: '', url: '/broken' },
          { label: 'no-url', url: '' },
        ],
      }),
    )

    expect(config.social).toEqual([{ platform: 'x', url: 'https://x.com/ayane' }])
    expect(config.legalLinks).toEqual([{ label: 'プライバシーポリシー', url: '/privacy' }])
  })

  it('defaults showPoweredBy to true and honours explicit false', () => {
    expect(parseFooterConfig('{}').showPoweredBy).toBe(true)
    expect(parseFooterConfig('{"showPoweredBy":false}').showPoweredBy).toBe(false)
  })

  it('round-trips through serializeFooterConfig', () => {
    const config = {
      social: [{ platform: 'line' as const, url: 'https://line.me/x' }],
      legalLinks: [{ label: '特定商取引法に基づく表記', url: '/tokushoho' }],
      showPoweredBy: false,
    }
    expect(parseFooterConfig(serializeFooterConfig(config))).toEqual(config)
  })
})
