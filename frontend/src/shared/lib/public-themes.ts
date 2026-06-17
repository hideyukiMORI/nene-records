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
      'Warm photo-magazine — terracotta/clay warmth on warm paper, photo-forward media, soft round corners and a friendly editorial voice.',
    author: 'NeNe Records',
    version: '2.0.0',
    createdAt: '2026-05-24',
    thumbnail: '/theme-thumbnails/consumer.webp',
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
      'Cool, structural tech — teal on cool paper. A wide, ruled, indexed blueprint layout with hard hairlines and a mono register.',
    author: 'NeNe Records',
    version: '3.0.0',
    createdAt: '2026-06-16',
    thumbnail: '/theme-thumbnails/aurora.webp',
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
      'An intimate single book — warm cream paper, quiet brown ink, a narrow serif-led measure with wide margins and hairline rules.',
    author: 'NeNe Records',
    version: '3.0.0',
    createdAt: '2026-06-16',
    thumbnail: '/theme-thumbnails/reading.webp',
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
  {
    id: 'swiss',
    name: 'Swiss',
    description:
      'Swiss / International modernism — achromatic paper, a single signal red, oversized grotesk and a strict baseline grid.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/swiss.webp',
    preview: {
      surface: 'oklch(99% 0 0)',
      raised: 'oklch(99.5% 0 0)',
      accent: 'oklch(56% 0.2 27)',
    },
  },
  {
    id: 'luxe',
    name: 'Editorial Luxe',
    description:
      'High-fashion editorial — black and gold, a high-contrast Didone serif and dramatic, theatrical whitespace.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/luxe.webp',
    preview: {
      surface: 'oklch(97% 0.006 80)',
      raised: 'oklch(99% 0.006 80)',
      accent: 'oklch(66% 0.13 85)',
    },
  },
  {
    id: 'synthwave',
    name: 'Synthwave',
    description:
      'Retro-future neon — an indigo night, magenta/cyan glow and an 80s grid horizon. Nostalgic and decorative.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/synthwave.webp',
    preview: {
      surface: 'oklch(96% 0.02 300)',
      raised: 'oklch(98% 0.02 300)',
      accent: 'oklch(58% 0.22 350)',
    },
  },
  {
    id: 'japandi',
    name: 'Japandi',
    description:
      'Zen minimalism — warm stone/beige, the widest breathing space, thin humanist type and pared-back chrome.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/japandi.webp',
    preview: {
      surface: 'oklch(95% 0.008 80)',
      raised: 'oklch(97% 0.008 80)',
      accent: 'oklch(50% 0.04 250)',
    },
  },
  {
    id: 'funk',
    name: 'Funk',
    description:
      'Retro 70s funk — mustard, rust and avocado, big rounded display type and a groovy, warm rhythm.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/funk.webp',
    preview: {
      surface: 'oklch(95% 0.03 85)',
      raised: 'oklch(97% 0.03 85)',
      accent: 'oklch(62% 0.15 55)',
    },
  },
  {
    id: 'academia',
    name: 'Dark Academia',
    description:
      'Dark academia — burgundy and forest on parchment, a classic serif voice and ornate scholarly rules.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/academia.webp',
    preview: {
      surface: 'oklch(94% 0.02 85)',
      raised: 'oklch(96% 0.02 85)',
      accent: 'oklch(46% 0.13 25)',
    },
  },
  {
    id: 'terminal',
    name: 'Terminal',
    description:
      'Hacker terminal — phosphor green on near-black, an all-mono register and a faint CRT scanline.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/terminal.webp',
    preview: {
      surface: 'oklch(96% 0.006 150)',
      raised: 'oklch(98% 0.006 150)',
      accent: 'oklch(52% 0.15 150)',
    },
  },
  {
    id: 'memphis',
    name: 'Memphis',
    description:
      'Memphis 80s pop — saturated primaries with black, geometric confetti and a bold graphic register.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/memphis.webp',
    preview: {
      surface: 'oklch(99% 0 0)',
      raised: 'oklch(99.5% 0 0)',
      accent: 'oklch(58% 0.21 25)',
    },
  },
  {
    id: 'coastal',
    name: 'Coastal',
    description:
      'Mediterranean coastal — azure, sand and white, airy whitespace and a relaxed humanist voice.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/coastal.webp',
    preview: {
      surface: 'oklch(98% 0.008 220)',
      raised: 'oklch(99.5% 0.008 220)',
      accent: 'oklch(56% 0.13 235)',
    },
  },
  {
    id: 'nebula',
    name: 'Nebula',
    description:
      'Gradient glassmorphism — soft violet/blue/pink gradients, frosted glass and a clean, futuristic SaaS feel.',
    author: 'NeNe Records',
    version: '1.0.0',
    createdAt: '2026-06-17',
    thumbnail: '/theme-thumbnails/nebula.webp',
    preview: {
      surface: 'oklch(98% 0.012 300)',
      raised: 'oklch(99.5% 0.012 300)',
      accent: 'oklch(58% 0.2 295)',
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
