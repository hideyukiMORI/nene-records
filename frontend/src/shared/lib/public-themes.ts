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
  {
    id: 'noir',
    name: 'Noir',
    description:
      'Dark-first, high-contrast editorial — inky near-black paper, an electric acid-lime accent and oversized display type. The bold counterpoint to the lighter three.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/noir.webp',
    preview: {
      surface: 'oklch(15% 0.012 280)',
      raised: 'oklch(19% 0.014 280)',
      accent: 'oklch(86% 0.21 128)',
    },
  },
  {
    id: 'botanical',
    name: 'Botanical',
    description:
      'Organic and unhurried — deep sage green on green-cream paper, wide breathing space, big round corners and soft warm shadows. A calm lifestyle / blog register.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/botanical.webp',
    preview: {
      surface: 'oklch(98% 0.009 120)',
      raised: 'oklch(99.5% 0.006 120)',
      accent: 'oklch(49% 0.105 150)',
    },
  },
  {
    id: 'pastel',
    name: 'Pastel Pop',
    description:
      'Playful multi-colour — faint plum-white paper, a coral lead with mint/blue secondary, big round corners and big floaty media. The fun axis.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/pastel.webp',
    preview: {
      surface: 'oklch(99% 0.012 320)',
      raised: 'oklch(99.7% 0.008 320)',
      accent: 'oklch(64% 0.16 8)',
    },
  },
  {
    id: 'newsprint',
    name: 'Newsprint',
    description:
      'Ink-on-paper journalism — warm newsprint cream, near-black ink, a newsprint-red accent, bold tight serif headlines and a dense, ruled, multi-column page.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/newsprint.webp',
    preview: {
      surface: 'oklch(95% 0.008 85)',
      raised: 'oklch(97% 0.006 85)',
      accent: 'oklch(50% 0.18 25)',
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
