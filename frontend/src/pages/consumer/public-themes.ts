/**
 * Registry of built-in public-site themes (epic #367 / Phase 1 #370).
 *
 * A theme is a set of design-token values scoped under `[data-theme='<id>']`
 * (light) and `[data-theme='<id>-dark']` (dark). The active theme is chosen by
 * the site admin via the `active_theme` public setting; the visitor light/dark
 * toggle (see use-consumer-theme.ts) picks the mode within the active theme.
 *
 * For now `consumer` is the sole built-in (consumer-brand.css). Additional
 * themes register here as their token CSS lands, per the contract in
 * docs/theming/public-theme-contract.md.
 */
export interface PublicThemeMeta {
  /** Theme id — used as the `[data-theme]` base value. */
  id: string
  /** Human label for the admin theme picker. */
  name: string
}

export const PUBLIC_THEMES: readonly PublicThemeMeta[] = [{ id: 'consumer', name: 'Terracotta' }]

export const DEFAULT_PUBLIC_THEME_ID = 'consumer'

/** Normalise a stored value to a known theme id, falling back to the default. */
export function resolvePublicThemeId(value: string | null | undefined): string {
  return PUBLIC_THEMES.some((theme) => theme.id === value)
    ? (value as string)
    : DEFAULT_PUBLIC_THEME_ID
}
