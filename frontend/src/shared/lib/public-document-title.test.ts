import { describe, expect, it } from 'vitest'
import { composePublicDocumentTitle } from './public-document-title'

describe('composePublicDocumentTitle', () => {
  it('appends the site name when the title does not carry it', () => {
    expect(composePublicDocumentTitle('『文学論』序', 'NeNe Records')).toBe(
      '『文学論』序 — NeNe Records',
    )
  })

  it('skips the suffix when the title already contains the site name (#909)', () => {
    expect(
      composePublicDocumentTitle(
        'サービスと料金｜彩音インターナショナル株式会社',
        '彩音インターナショナル株式会社',
      ),
    ).toBe('サービスと料金｜彩音インターナショナル株式会社')
  })

  it('falls back to the site name for an empty or missing title', () => {
    expect(composePublicDocumentTitle('  ', 'NeNe Records')).toBe('NeNe Records')
    expect(composePublicDocumentTitle(null, 'NeNe Records')).toBe('NeNe Records')
  })

  it('keeps the bare title when the site name is empty', () => {
    expect(composePublicDocumentTitle('ページ名', '')).toBe('ページ名')
  })
})
