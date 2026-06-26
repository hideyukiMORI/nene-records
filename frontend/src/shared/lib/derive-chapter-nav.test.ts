import { describe, expect, it } from 'vitest'
import { deriveChapterNav } from './derive-chapter-nav'

const SLUG_PATTERN = '/{type}/{slug}'

describe('deriveChapterNav', () => {
  it('builds prev/index/next URLs for a middle chapter (slug permalink)', () => {
    expect(
      deriveChapterNav({
        typeSlug: 'work',
        pattern: SLUG_PATTERN,
        series: 'aozora-000148-752',
        chapterNo: 2,
        chapterTotal: 11,
      }),
    ).toEqual({
      indexUrl: '/work/aozora-000148-752',
      prevUrl: '/work/aozora-000148-752-1',
      nextUrl: '/work/aozora-000148-752-3',
      chapterNo: 2,
      chapterTotal: 11,
    })
  })

  it('omits the previous link on the first chapter', () => {
    const nav = deriveChapterNav({
      typeSlug: 'work',
      pattern: SLUG_PATTERN,
      series: 'w',
      chapterNo: 1,
      chapterTotal: 3,
    })
    expect(nav?.prevUrl).toBeNull()
    expect(nav?.nextUrl).toBe('/work/w-2')
  })

  it('omits the next link on the last chapter', () => {
    const nav = deriveChapterNav({
      typeSlug: 'work',
      pattern: SLUG_PATTERN,
      series: 'w',
      chapterNo: 3,
      chapterTotal: 3,
    })
    expect(nav?.prevUrl).toBe('/work/w-2')
    expect(nav?.nextUrl).toBeNull()
  })

  it('returns null when there is no series', () => {
    expect(
      deriveChapterNav({
        typeSlug: 'work',
        pattern: SLUG_PATTERN,
        series: null,
        chapterNo: 1,
        chapterTotal: 3,
      }),
    ).toBeNull()
    expect(
      deriveChapterNav({
        typeSlug: 'work',
        pattern: SLUG_PATTERN,
        series: '   ',
        chapterNo: 1,
        chapterTotal: 3,
      }),
    ).toBeNull()
  })

  it('returns null for missing, out-of-range or non-integer chapter numbers', () => {
    const base = { typeSlug: 'work', pattern: SLUG_PATTERN, series: 'w' }
    expect(deriveChapterNav({ ...base, chapterNo: null, chapterTotal: 3 })).toBeNull()
    expect(deriveChapterNav({ ...base, chapterNo: 4, chapterTotal: 3 })).toBeNull()
    expect(deriveChapterNav({ ...base, chapterNo: 0, chapterTotal: 3 })).toBeNull()
    expect(deriveChapterNav({ ...base, chapterNo: Number.NaN, chapterTotal: 3 })).toBeNull()
  })
})
