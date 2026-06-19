/**
 * Turn a theme customization (a base theme + knob/colour/flag overrides) into a
 * full runtime-theme manifest, so an admin can save their tweaks as a new theme
 * via createTheme — the same data-driven theme system ClaudeDesign uses (#454).
 *
 * The base theme supplies the full contract token set (from base-theme-tokens,
 * extracted from the built-in CSS); the overrides are layered on per the same
 * mapping the live customizer uses (resolveOverrideStyle + resolveModeColors).
 */
import { BASE_THEME_TOKENS, type BaseThemeTokens } from './base-theme-tokens'

export type { BaseThemeTokens } from './base-theme-tokens'
import { resolveModeColors, resolveOverrideStyle, type ThemeOverrides } from './theme-customization'

export interface ThemeManifestDraft {
  id: string
  name: string
  version: string
  supportsModes: ('light' | 'dark')[]
  tokens: { light: Record<string, string>; dark: Record<string, string> }
  description?: string
  flags?: Record<string, string>
}

/** Built-in theme ids — runtime themes must not shadow them. */
export function builtInThemeIds(): ReadonlySet<string> {
  return new Set(Object.keys(BASE_THEME_TOKENS))
}

/** Resolved full token set of a base theme, or undefined if not a built-in. */
export function baseThemeTokens(themeId: string): BaseThemeTokens | undefined {
  return BASE_THEME_TOKENS[themeId]
}

/** A valid theme id (^[a-z][a-z0-9-]{1,40}$) derived from a display name. */
export function slugifyThemeId(name: string): string {
  let s = name
    .toLowerCase()
    .normalize('NFKD')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-+|-+$)/g, '')
  if (s === '') s = 'theme'
  if (!/^[a-z]/.test(s)) s = `t-${s}`
  return s.slice(0, 41)
}

/** A theme id not already taken (built-in ids + existing runtime keys). */
export function uniqueThemeId(base: string, taken: ReadonlySet<string>): string {
  if (!taken.has(base)) return base
  for (let i = 2; i < 1000; i++) {
    const candidate = `${base.slice(0, 37)}-${String(i)}`
    if (!taken.has(candidate)) return candidate
  }
  return `${base.slice(0, 30)}-${Date.now().toString(36)}`
}

function stripVarPrefix(style: Record<string, string>): Record<string, string> {
  const out: Record<string, string> = {}
  for (const [key, value] of Object.entries(style)) {
    out[key.startsWith('--') ? key.slice(2) : key] = value
  }
  return out
}

/**
 * Build a full manifest from base tokens + overrides. Per mode: base tokens,
 * then mode-agnostic knob overrides, then mode-specific colour overrides.
 */
export function buildThemeManifestFromCustomization(args: {
  id: string
  name: string
  description?: string
  baseTokens: BaseThemeTokens
  overrides: ThemeOverrides
}): ThemeManifestDraft {
  const { id, name, description, baseTokens, overrides } = args
  const agnostic = stripVarPrefix(resolveOverrideStyle(overrides))
  const tokensFor = (mode: 'light' | 'dark'): Record<string, string> => ({
    ...baseTokens[mode],
    ...agnostic,
    ...stripVarPrefix(resolveModeColors(overrides, mode)),
  })

  const manifest: ThemeManifestDraft = {
    id,
    name,
    version: '1.0.0',
    supportsModes: ['light', 'dark'],
    tokens: { light: tokensFor('light'), dark: tokensFor('dark') },
  }
  if (description !== undefined && description.trim() !== '') {
    manifest.description = description.trim()
  }
  if (overrides.flags !== undefined && Object.keys(overrides.flags).length > 0) {
    manifest.flags = overrides.flags as Record<string, string>
  }
  return manifest
}
