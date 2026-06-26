import { resolvePermalink } from './resolve-permalink'

/**
 * Reserved field keys that drive the derived chapter navigation. Mirrors the
 * backend `ChapterNavBuilder::FIELD_KEYS`. These are structural metadata
 * (which work / which position) and are never shown as ordinary record fields.
 */
export const CHAPTER_NAV_FIELD_KEYS: readonly string[] = ['series', 'chapter_no', 'chapter_total']

export interface ChapterNav {
  /** URL of the work's 目次 (index) record — the canonical front door. */
  indexUrl: string
  /** URL of the previous chapter, or null on the first chapter. */
  prevUrl: string | null
  /** URL of the next chapter, or null on the last chapter. */
  nextUrl: string | null
  chapterNo: number
  chapterTotal: number
}

export interface DeriveChapterNavInput {
  typeSlug: string
  pattern: string | null | undefined
  series: string | null
  chapterNo: number | null
  chapterTotal: number | null
}

/**
 * Derive the chapter navigation from a record's series / chapter_no /
 * chapter_total. Returns null unless the record is a valid chapter (1..total) of
 * a multi-chapter work. Sibling URLs are resolved with the type's permalink
 * pattern, so a work using a slug permalink (`/{type}/{slug}`) yields correct
 * URLs with no extra fetch. Mirrors the backend `ChapterNavBuilder` so the SSR
 * and the SPA agree.
 */
export function deriveChapterNav({
  typeSlug,
  pattern,
  series,
  chapterNo,
  chapterTotal,
}: DeriveChapterNavInput): ChapterNav | null {
  const trimmedSeries = series?.trim() ?? ''

  if (
    trimmedSeries === '' ||
    chapterNo === null ||
    chapterTotal === null ||
    !Number.isInteger(chapterNo) ||
    !Number.isInteger(chapterTotal) ||
    chapterTotal < 1 ||
    chapterNo < 1 ||
    chapterNo > chapterTotal
  ) {
    return null
  }

  const url = (slug: string): string =>
    resolvePermalink(pattern, { typeSlug, entitySlug: slug, entityId: 0, publishedAt: null })

  return {
    indexUrl: url(trimmedSeries),
    prevUrl: chapterNo > 1 ? url(`${trimmedSeries}-${String(chapterNo - 1)}`) : null,
    nextUrl: chapterNo < chapterTotal ? url(`${trimmedSeries}-${String(chapterNo + 1)}`) : null,
    chapterNo,
    chapterTotal,
  }
}
