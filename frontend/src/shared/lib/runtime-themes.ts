/**
 * Runtime (data-driven) theme application (#423 Phase D).
 *
 * A runtime theme's manifest carries the full token set per mode. Instead of a
 * built theme's static `[data-theme]` CSS, we emit those tokens as a scoped
 * `<style>` at render time — the same `<style>` channel the customizer already
 * uses for overrides, generalised from "overrides" to "full token set".
 *
 * Defence in depth: even though the server validates/sanitises manifests on
 * write, we re-guard here — only token keys matching the contract pattern and
 * values free of CSS break-out / external constructs are emitted. Anything else
 * is dropped, never the raw string.
 */

export interface RuntimeThemeManifest {
  id: string
  name: string
  version: string
  description?: string
  author?: string
  tokens?: {
    light?: Record<string, unknown>
    dark?: Record<string, unknown>
  }
  flags?: Record<string, string>
  assets?: Record<string, unknown>
}

export interface RuntimeTheme {
  theme_key: string
  name: string
  version: string
  manifest: RuntimeThemeManifest
}

const THEME_KEY = /^[a-z][a-z0-9-]{1,40}$/
const TOKEN_KEY = /^[a-z][a-z0-9-]*$/
const BREAKOUT = /[;{}<>\\`]/
const UNSAFE_SUBSTRINGS = ['url(', '@import', 'expression(', 'javascript:', '/*', '*/']

/** A token value is safe when it cannot break out of `--key: value;` / <style>. */
export function isSafeTokenValue(value: string): boolean {
  const trimmed = value.trim()
  if (trimmed === '' || trimmed.length > 200 || BREAKOUT.test(trimmed)) {
    return false
  }
  const lower = trimmed.toLowerCase()
  return !UNSAFE_SUBSTRINGS.some((needle) => lower.includes(needle))
}

function emitTokens(selector: string, tokens: Record<string, unknown>): string {
  const decls = Object.entries(tokens)
    .filter(
      ([key, value]) => TOKEN_KEY.test(key) && typeof value === 'string' && isSafeTokenValue(value),
    )
    .map(([key, value]) => `--${key}:${(value as string).trim()};`)
    .join('')
  return decls === '' ? '' : `.nene-public[data-theme='${selector}']{${decls}}`
}

/**
 * Build the scoped stylesheet for a runtime theme: token declarations for the
 * light (`<key>`) and dark (`<key>-dark`) `[data-theme]` values. Returns '' for
 * an invalid key or empty token sets.
 */
export function buildThemeStylesheet(themeKey: string, manifest: RuntimeThemeManifest): string {
  if (!THEME_KEY.test(themeKey)) {
    return ''
  }
  const light = manifest.tokens?.light ?? {}
  const dark = manifest.tokens?.dark ?? {}
  return emitTokens(themeKey, light) + emitTokens(`${themeKey}-dark`, dark)
}

// ── First-paint (FOUC) cache ───────────────────────────────────────────────
// Runtime themes are fetched async (React Query), so on first paint the
// stylesheet isn't ready and a non-built-in active theme would flash the
// default. We cache the resolved CSS + flag attrs in localStorage (same
// FOUC-avoidance the customizer overrides use) and apply them synchronously
// until the live fetch settles. Built-in themes cache empty values.

const RUNTIME_THEME_STORAGE_KEY = 'nene_public_runtime_theme'

export interface StoredRuntimeTheme {
  /** Scoped `<style>` body for the active runtime theme ('' for built-in). */
  css: string
  /** Structural flag `data-*` attributes from the active runtime theme's manifest. */
  flags: Record<string, string>
}

const EMPTY_STORED: StoredRuntimeTheme = { css: '', flags: {} }

export function readStoredRuntimeTheme(): StoredRuntimeTheme {
  if (typeof window === 'undefined') {
    return EMPTY_STORED
  }
  try {
    const raw = window.localStorage.getItem(RUNTIME_THEME_STORAGE_KEY)
    if (raw === null) {
      return EMPTY_STORED
    }
    const data = JSON.parse(raw) as unknown
    if (typeof data !== 'object' || data === null) {
      return EMPTY_STORED
    }
    const record = data as Record<string, unknown>
    return {
      css: typeof record.css === 'string' ? record.css : '',
      flags:
        typeof record.flags === 'object' && record.flags !== null
          ? (record.flags as Record<string, string>)
          : {},
    }
  } catch {
    return EMPTY_STORED
  }
}

export function storeRuntimeTheme(value: StoredRuntimeTheme): void {
  if (typeof window === 'undefined') {
    return
  }
  try {
    window.localStorage.setItem(RUNTIME_THEME_STORAGE_KEY, JSON.stringify(value))
  } catch {
    // Ignore quota / serialization errors — the cache is best-effort.
  }
}

/** Derive the picker's 3-colour swatch from the light token set (#426 B). */
export function swatchFromManifest(manifest: RuntimeThemeManifest): {
  surface: string
  raised: string
  accent: string
} {
  const t = manifest.tokens?.light ?? {}
  const pick = (key: string, fallback: string): string => {
    const value = t[key]
    return typeof value === 'string' && isSafeTokenValue(value) ? value : fallback
  }
  return {
    surface: pick('color-surface', '#ffffff'),
    raised: pick('color-surface-raised', '#ffffff'),
    accent: pick('color-accent', '#888888'),
  }
}
