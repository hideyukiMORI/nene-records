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
  /** Mini-swatch colours for the picker (light-mode tokens). */
  preview: { surface: string; raised: string; accent: string }
}

export const PUBLIC_THEMES: readonly PublicThemeMeta[] = [
  {
    id: 'consumer',
    name: 'Terracotta',
    preview: {
      surface: 'oklch(97.5% 0.012 75)',
      raised: 'oklch(99.5% 0.008 75)',
      accent: 'oklch(58% 0.14 45)',
    },
  },
  {
    id: 'aurora',
    name: 'Aurora',
    preview: {
      surface: 'oklch(98% 0.006 230)',
      raised: 'oklch(99.5% 0.004 230)',
      accent: 'oklch(62% 0.13 200)',
    },
  },
  {
    id: 'reading',
    name: 'Reading',
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
