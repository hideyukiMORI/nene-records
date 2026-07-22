import { describe, expect, it } from 'vitest'

import { stripSiteNameSuffix } from './strip-site-name-suffix'

describe('stripSiteNameSuffix', () => {
  it('strips a fullwidth-bar site suffix (authored meta_title convention)', () => {
    expect(
      stripSiteNameSuffix(
        '会社案内｜彩音インターナショナル株式会社',
        '彩音インターナショナル株式会社',
      ),
    ).toBe('会社案内')
    expect(
      stripSiteNameSuffix(
        '代表紹介 森 秀之｜彩音インターナショナル株式会社',
        '彩音インターナショナル株式会社',
      ),
    ).toBe('代表紹介 森 秀之')
  })

  it('strips an em-dash site suffix (product-composed convention)', () => {
    expect(stripSiteNameSuffix('サービスと料金 — NeNe Records', 'NeNe Records')).toBe(
      'サービスと料金',
    )
  })

  it('leaves the title untouched when it is exactly the site name (front page)', () => {
    expect(
      stripSiteNameSuffix('彩音インターナショナル株式会社', '彩音インターナショナル株式会社'),
    ).toBe('彩音インターナショナル株式会社')
  })

  it('leaves the title untouched when the site name is not a trailing suffix', () => {
    // Front page: site name leads, tagline trails — nothing to strip.
    expect(
      stripSiteNameSuffix(
        '彩音インターナショナル株式会社｜見積書より先に、動くものを。',
        '彩音インターナショナル株式会社',
      ),
    ).toBe('彩音インターナショナル株式会社｜見積書より先に、動くものを。')
  })

  it('is a no-op when the site name is empty or whitespace', () => {
    expect(stripSiteNameSuffix('会社案内｜彩音', '')).toBe('会社案内｜彩音')
    expect(stripSiteNameSuffix('会社案内', '   ')).toBe('会社案内')
  })

  it('never returns an empty string even if the head collapses to separators', () => {
    expect(stripSiteNameSuffix('｜彩音', '彩音')).toBe('｜彩音')
  })
})
