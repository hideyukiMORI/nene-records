/**
 * Live theme-preview protocol (#538 ②). The admin customizer embeds a public
 * page in an iframe (`?nene-theme-preview=1`) and posts the in-progress (unsaved)
 * theme to it via `postMessage`; the public shell, in preview mode, applies that
 * draft instead of the saved settings. Both sides are same-origin, so messages
 * are origin-checked against `window.location.origin`.
 */

import type { ThemeLogo } from './theme-customization'

export const THEME_PREVIEW_PARAM = 'nene-theme-preview'

/** Customizer → iframe: apply this draft theme. */
export const THEME_PREVIEW_APPLY = 'nene-theme-preview:apply'

/** Iframe → customizer: preview shell mounted, send the current draft. */
export const THEME_PREVIEW_READY = 'nene-theme-preview:ready'

export interface ThemePreviewApplyMessage {
  type: typeof THEME_PREVIEW_APPLY
  /** Token-override CSS for the active theme (from `overrideCssForTheme`). */
  overrideCss: string
  /** Structural flag attributes (`data-*` → value) for the `.nene-public` root. */
  flagAttrs: Record<string, string>
  /** Per-mode logo URLs (rendered as `<img>`, so not part of overrideCss). #372. */
  themeLogo?: ThemeLogo
}

export interface ThemePreviewReadyMessage {
  type: typeof THEME_PREVIEW_READY
}

/** True when the current document was opened as a theme-preview surface. */
export function isThemePreviewRequest(search: string): boolean {
  return new URLSearchParams(search).has(THEME_PREVIEW_PARAM)
}
