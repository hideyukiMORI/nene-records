/**
 * Registry of built-in public-site themes (epic #367 / Phase 1 #370).
 *
 * A theme is a set of design-token values scoped under `[data-theme='<id>']`
 * (light) and `[data-theme='<id>-dark']` (dark). The active theme is chosen by
 * the site admin via the `active_theme` public setting; the visitor light/dark
 * toggle (see pages/consumer/use-consumer-theme.ts) picks the mode within it.
 *
 * Shared so both the public site (resolution) and the admin theme picker
 * (manage-appearance) read the same list. Additional themes register here as
 * their token CSS lands, per docs/theming/public-theme-contract.md.
 */
export interface PublicThemeMeta {
  /** Theme id — used as the `[data-theme]` base value. */
  id: string
  /** Human label for the admin theme picker. */
  name: string
  /** One-line description shown on the picker card (mirrors the bundle manifest). */
  description: string
  /** Theme author / vendor. */
  author: string
  /** Semantic version — mirrors the bundle manifest `version`. */
  version: string
  /** ISO date (YYYY-MM-DD) the theme was first added. Shown on the card. */
  createdAt: string
  /** Mini-swatch colours for the picker (light-mode tokens). Fallback when no thumbnail. */
  preview: { surface: string; raised: string; accent: string }
  /**
   * Optional preview image for the picker card (WordPress-style screenshot).
   * App-absolute path; when absent the colour swatch is shown instead. Built-in
   * themes drop a file at `public/theme-thumbnails/<id>.webp` (served at
   * `/theme-thumbnails/<id>.webp`); delivered bundles map their
   * `assets.preview` here at import time. See public/theme-thumbnails/README.md.
   */
  thumbnail?: string
}

export const PUBLIC_THEMES: readonly PublicThemeMeta[] = [
  {
    id: 'consumer',
    name: 'Terracotta',
    description:
      'Warm, soft editorial magazine — terracotta accent on warm paper. The standard baseline: rounded chrome and gentle shadows.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-05-24',
    preview: {
      surface: 'oklch(97.5% 0.012 75)',
      raised: 'oklch(99.5% 0.008 75)',
      accent: 'oklch(58% 0.14 45)',
    },
  },
  {
    id: 'aurora',
    name: 'Aurora',
    description:
      'Cool, structural tech — teal on cool paper. A ruled, indexed blueprint layout with hard hairlines and a mono register.',
    author: 'NeNe Records',
    version: '2.0.0',
    createdAt: '2026-06-16',
    preview: {
      surface: 'oklch(98% 0.006 230)',
      raised: 'oklch(99.5% 0.004 230)',
      accent: 'oklch(62% 0.13 200)',
    },
  },
  {
    id: 'reading',
    name: 'Reading',
    description:
      'Intimate single-book reading — serif headings, a narrow measure with wide margins, hairline rules and a literary drop cap.',
    author: 'NeNe Records',
    version: '2.0.0',
    createdAt: '2026-06-16',
    preview: {
      surface: 'oklch(97% 0.02 85)',
      raised: 'oklch(99% 0.014 85)',
      accent: 'oklch(46% 0.07 58)',
    },
  },
]

export const DEFAULT_PUBLIC_THEME_ID = 'consumer'

/** Normalise a stored value to a known theme id, falling back to the default. */
export function resolvePublicThemeId(value: string | null | undefined): string {
  return PUBLIC_THEMES.some((theme) => theme.id === value)
    ? (value as string)
    : DEFAULT_PUBLIC_THEME_ID
}

// The last theme the site resolved to, cached so the first paint can apply it
// synchronously while the public settings request is still in flight — avoids a
// default→saved theme flash (FOUC) for returning visitors.
const ACTIVE_THEME_STORAGE_KEY = 'nene_public_active_theme'

export function readStoredActiveTheme(): string {
  if (typeof window === 'undefined') {
    return DEFAULT_PUBLIC_THEME_ID
  }
  return resolvePublicThemeId(window.localStorage.getItem(ACTIVE_THEME_STORAGE_KEY))
}

export function storeActiveTheme(themeId: string): void {
  if (typeof window === 'undefined') {
    return
  }
  window.localStorage.setItem(ACTIVE_THEME_STORAGE_KEY, resolvePublicThemeId(themeId))
}
