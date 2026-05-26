/**
 * Permalink pattern resolver for public entity URLs.
 *
 * Supported tokens:
 *   {type}  – entity type slug (e.g. "posts")
 *   {slug}  – entity slug      (e.g. "my-article")
 *   {id}    – entity numeric ID
 *   {year}  – 4-digit year from publishedAt (or createdAt, or "0000")
 *   {month} – 2-digit month (01–12)
 *   {day}   – 2-digit day   (01–31)
 */

export const DEFAULT_PERMALINK_PATTERN = '/{type}/{id}'

export interface PermalinkContext {
  typeSlug: string
  entitySlug: string | null
  entityId: number
  publishedAt: string | null // ISO-8601
}

/** Built-in preset patterns, mirroring WordPress permalink settings. */
export const PERMALINK_PRESETS = [
  {
    id: 'default',
    label: 'Default',
    labelJa: 'デフォルト',
    pattern: '/{type}/{id}',
    example: '/posts/42',
  },
  {
    id: 'post-name',
    label: 'Post name',
    labelJa: '投稿名',
    pattern: '/{type}/{slug}',
    example: '/posts/my-article',
  },
  {
    id: 'month-name',
    label: 'Month and name',
    labelJa: '月と投稿名',
    pattern: '/{type}/{year}/{month}/{slug}',
    example: '/posts/2024/01/my-article',
  },
  {
    id: 'day-name',
    label: 'Day and name',
    labelJa: '日付と投稿名',
    pattern: '/{type}/{year}/{month}/{day}/{slug}',
    example: '/posts/2024/01/15/my-article',
  },
] as const

export type PermalinkPresetId = (typeof PERMALINK_PRESETS)[number]['id']

/**
 * Resolve a permalink pattern with the given context.
 * Returns a URL path string starting with "/".
 */
export function resolvePermalink(
  pattern: string | null | undefined,
  ctx: PermalinkContext,
): string {
  const pat = pattern ?? DEFAULT_PERMALINK_PATTERN

  const date = ctx.publishedAt ? new Date(ctx.publishedAt) : null
  const year = date ? String(date.getUTCFullYear()) : '0000'
  const month = date ? String(date.getUTCMonth() + 1).padStart(2, '0') : '00'
  const day = date ? String(date.getUTCDate()).padStart(2, '0') : '00'

  return pat
    .replace(/\{type\}/g, ctx.typeSlug)
    .replace(/\{slug\}/g, ctx.entitySlug ?? String(ctx.entityId))
    .replace(/\{id\}/g, String(ctx.entityId))
    .replace(/\{year\}/g, year)
    .replace(/\{month\}/g, month)
    .replace(/\{day\}/g, day)
}

/**
 * Given a permalink pattern and the "splat" path segment (everything after the
 * entity type slug in a `/:typeSlug/*` route), extract what we need to look up
 * the entity.
 *
 * Returns `{ kind: 'id', id: number }` or `{ kind: 'slug', slug: string }`.
 */
export type EntityLookupKey = { kind: 'id'; id: number } | { kind: 'slug'; slug: string }

export function extractEntityKeyFromSplat(
  pattern: string | null | undefined,
  splat: string,
): EntityLookupKey {
  const pat = pattern ?? DEFAULT_PERMALINK_PATTERN

  // Count the token segments in the pattern after {type}
  // e.g. "/{type}/{year}/{month}/{slug}" → after {type} we have 3 more segments
  // The last token in the pattern determines how we resolve
  if (pat.includes('{id}') && !pat.includes('{slug}')) {
    const id = Number(splat.split('/').at(-1))
    if (!Number.isNaN(id)) return { kind: 'id', id }
  }

  // Default: use the last path segment as slug
  const slug = splat.split('/').filter(Boolean).at(-1) ?? splat
  return { kind: 'slug', slug }
}

/**
 * Returns true when the pattern requires slug (not just numeric ID) in the URL.
 */
export function patternUsesSlug(pattern: string | null | undefined): boolean {
  return (pattern ?? DEFAULT_PERMALINK_PATTERN).includes('{slug}')
}
