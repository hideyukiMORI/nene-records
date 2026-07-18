import { describe, expect, it } from 'vitest'
import {
  extractEntityKeyFromSplat,
  patternUsesSlug,
  resolvePermalink,
  type PermalinkContext,
} from './resolve-permalink'

const ctx: PermalinkContext = {
  typeSlug: 'posts',
  entitySlug: 'my-article',
  entityId: 42,
  publishedAt: '2026-07-18T09:30:00+00:00',
}

describe('resolvePermalink', () => {
  it('falls back to the default pattern /{type}/{id} for null/undefined patterns', () => {
    expect(resolvePermalink(null, ctx)).toBe('/posts/42')
    expect(resolvePermalink(undefined, ctx)).toBe('/posts/42')
  })

  it('expands each built-in preset', () => {
    expect(resolvePermalink('/{type}/{id}', ctx)).toBe('/posts/42')
    expect(resolvePermalink('/{type}/{slug}', ctx)).toBe('/posts/my-article')
    expect(resolvePermalink('/{type}/{year}/{month}/{slug}', ctx)).toBe('/posts/2026/07/my-article')
    expect(resolvePermalink('/{type}/{year}/{month}/{day}/{slug}', ctx)).toBe(
      '/posts/2026/07/18/my-article',
    )
  })

  it('substitutes the numeric id when the slug is missing', () => {
    expect(resolvePermalink('/{type}/{slug}', { ...ctx, entitySlug: null })).toBe('/posts/42')
  })

  it('uses 0000/00/00 placeholders when publishedAt is missing', () => {
    expect(
      resolvePermalink('/{type}/{year}/{month}/{day}/{slug}', { ...ctx, publishedAt: null }),
    ).toBe('/posts/0000/00/00/my-article')
  })

  it('derives date tokens in UTC, not the browser timezone', () => {
    // JST 元日 00:30 は UTC ではまだ前年の大晦日 — SSR（サーバ）と URL が割れない
    // よう、日付トークンは常に UTC で切る。
    expect(
      resolvePermalink('/{type}/{year}/{month}/{day}/{slug}', {
        ...ctx,
        publishedAt: '2026-01-01T00:30:00+09:00',
      }),
    ).toBe('/posts/2025/12/31/my-article')
  })
})

describe('extractEntityKeyFromSplat', () => {
  it('extracts a numeric id for id-only patterns', () => {
    expect(extractEntityKeyFromSplat('/{type}/{id}', '42')).toEqual({ kind: 'id', id: 42 })
    expect(extractEntityKeyFromSplat(null, '42')).toEqual({ kind: 'id', id: 42 })
  })

  it('uses the last path segment as slug for slug patterns', () => {
    expect(extractEntityKeyFromSplat('/{type}/{slug}', 'my-article')).toEqual({
      kind: 'slug',
      slug: 'my-article',
    })
    expect(
      extractEntityKeyFromSplat('/{type}/{year}/{month}/{slug}', '2026/07/my-article'),
    ).toEqual({ kind: 'slug', slug: 'my-article' })
  })

  it('ignores a trailing slash when picking the slug segment', () => {
    expect(extractEntityKeyFromSplat('/{type}/{slug}', 'my-article/')).toEqual({
      kind: 'slug',
      slug: 'my-article',
    })
  })

  it('falls back to slug lookup when the id segment is not numeric', () => {
    expect(extractEntityKeyFromSplat('/{type}/{id}', 'not-a-number')).toEqual({
      kind: 'slug',
      slug: 'not-a-number',
    })
  })
})

describe('patternUsesSlug', () => {
  it('reports whether the pattern needs a slug in the URL', () => {
    expect(patternUsesSlug('/{type}/{slug}')).toBe(true)
    expect(patternUsesSlug('/{type}/{id}')).toBe(false)
    // 既定パターンは id ベース
    expect(patternUsesSlug(null)).toBe(false)
    expect(patternUsesSlug(undefined)).toBe(false)
  })
})
