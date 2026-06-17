/**
 * Theme customizer (epic #367 / Phase 2 #372).
 *
 * Admins tweak a small set of "knobs" per theme; the values are stored as JSON
 * in the public `theme_overrides` setting and applied as inline CSS custom
 * properties on the public `.nene-public` root (highest specificity, so they
 * override the active theme's tokens). Values are constrained/sanitised here —
 * we only ever emit known CSS variables with validated values, never raw input.
 *
 * Knobs: accent (色) / body font (フォント) / content width (幅) / gutter (余白)
 * / radius (角丸) / font size × type scale (→ `--text-*`) / density (→ `--space-*`).
 * Derived tokens that reference these (e.g. `--color-accent-weak`) auto-follow.
 */

/** Per-theme override values as stored (one entry per theme id). */
export interface ThemeOverrides {
  /** Accent colour — hex (`#rrggbb`). */
  accent?: string
  /** Body font key (see FONT_OPTIONS). Maps to `--font-sans`. */
  fontBody?: string
  /** Content width preset key (see WIDTH_OPTIONS). Maps to `--content-w`. */
  contentWidth?: string
  /** Gutter preset key (see GUTTER_OPTIONS). Maps to `--gutter`. */
  gutter?: string
  /** Corner radius preset key (see RADIUS_OPTIONS). */
  radius?: string
  /** Base body font-size preset (see FONT_SIZE_OPTIONS). Recomputes `--text-*`. */
  fontSize?: string
  /** Type scale preset (see TYPE_SCALE_OPTIONS). Recomputes `--text-*`. */
  typeScale?: string
  /** Spacing density preset (see DENSITY_OPTIONS). Scales `--space-*`. */
  density?: string
  /** Paper / surface colour, per mode (hex). Derives the surface family. */
  surface?: ColorPair
  /** Body text ink colour, per mode (hex). Derives `--color-text-muted`. */
  text?: ColorPair
  /** Structural style flags (applied as data-* attributes; see theme-flags.md). */
  flags?: ThemeFlags
}

/** A colour value per light/dark mode (hex). */
export interface ColorPair {
  light?: string
  dark?: string
}

/** Structural style flags — enumerated; implemented once in base CSS. */
export interface ThemeFlags {
  feedLayout?: string
  feedColumns?: string
  cardStyle?: string
  media?: string
  hero?: string
  sectionRule?: string
  eyebrow?: string
}

/** flag key → { data attribute, allowed values }. Mirrors theme-flags.md §2. */
export const FLAG_DEFS: Record<keyof ThemeFlags, { attr: string; options: readonly KnobOption[] }> =
  {
    feedLayout: {
      attr: 'data-feed',
      options: [
        { value: 'grid', label: 'Grid' },
        { value: 'list', label: 'List' },
        { value: 'magazine', label: 'Magazine' },
      ],
    },
    feedColumns: {
      attr: 'data-feed-cols',
      options: [
        { value: 'auto', label: 'Auto' },
        { value: '1', label: '1' },
        { value: '2', label: '2' },
        { value: '3', label: '3' },
        { value: '4', label: '4' },
      ],
    },
    cardStyle: {
      attr: 'data-cards',
      options: [
        { value: 'flat', label: 'Flat' },
        { value: 'bordered', label: 'Bordered' },
        { value: 'shadowed', label: 'Shadowed' },
        { value: 'framed', label: 'Framed' },
      ],
    },
    media: {
      attr: 'data-media',
      options: [
        { value: 'plain', label: 'Plain' },
        { value: 'duotone', label: 'Duotone' },
        { value: 'grayscale', label: 'Grayscale' },
        { value: 'framed', label: 'Framed' },
      ],
    },
    hero: {
      attr: 'data-hero',
      options: [
        { value: 'standard', label: 'Standard' },
        { value: 'fullbleed', label: 'Full bleed' },
        { value: 'minimal', label: 'Minimal' },
      ],
    },
    sectionRule: {
      attr: 'data-rule',
      options: [
        { value: 'none', label: 'None' },
        { value: 'hairline', label: 'Hairline' },
        { value: 'heavy', label: 'Heavy' },
      ],
    },
    eyebrow: {
      attr: 'data-eyebrow',
      options: [
        { value: 'plain', label: 'Plain' },
        { value: 'caps', label: 'Caps' },
        { value: 'barred', label: 'Barred' },
      ],
    },
  }

export interface KnobOption {
  value: string
  label: string
}

/** Body font allowlist (all self-hosted via @fontsource). */
export const FONT_OPTIONS: readonly KnobOption[] = [
  { value: 'inter', label: 'Inter (sans)' },
  { value: 'source-serif', label: 'Source Serif 4 (serif)' },
  { value: 'saira', label: 'Saira Semi Condensed' },
  { value: 'space-grotesk', label: 'Space Grotesk' },
  { value: 'system', label: 'System sans' },
]

const FONT_STACKS: Record<string, string> = {
  inter: "'Inter', 'Noto Sans JP', ui-sans-serif, system-ui, sans-serif",
  'source-serif': "'Source Serif 4', 'Noto Serif JP', Georgia, 'Times New Roman', serif",
  saira: "'Saira Semi Condensed', 'Noto Sans JP', sans-serif",
  'space-grotesk': "'Space Grotesk', 'Noto Sans JP', sans-serif",
  system: 'ui-sans-serif, system-ui, sans-serif',
}

export const WIDTH_OPTIONS: readonly KnobOption[] = [
  { value: 'narrow', label: 'Narrow' },
  { value: 'default', label: 'Default' },
  { value: 'wide', label: 'Wide' },
]
const WIDTH_VALUES: Record<string, string> = {
  narrow: '960px',
  default: '1180px',
  wide: '1320px',
}

export const GUTTER_OPTIONS: readonly KnobOption[] = [
  { value: 'tight', label: 'Tight' },
  { value: 'default', label: 'Default' },
  { value: 'roomy', label: 'Roomy' },
]
const GUTTER_VALUES: Record<string, string> = {
  tight: 'clamp(1rem, 0.5rem + 2vw, 1.75rem)',
  default: 'clamp(1.25rem, 0.5rem + 3vw, 2.5rem)',
  roomy: 'clamp(1.75rem, 1rem + 4.5vw, 3.75rem)',
}

export const RADIUS_OPTIONS: readonly KnobOption[] = [
  { value: 'sharp', label: 'Sharp' },
  { value: 'soft', label: 'Soft' },
  { value: 'round', label: 'Round' },
]
const RADIUS_VALUES: Record<string, { sm: string; md: string; lg: string }> = {
  sharp: { sm: '2px', md: '2px', lg: '3px' },
  soft: { sm: '6px', md: '12px', lg: '20px' },
  round: { sm: '10px', md: '18px', lg: '28px' },
}

export const FONT_SIZE_OPTIONS: readonly KnobOption[] = [
  { value: 'small', label: 'Small' },
  { value: 'default', label: 'Default' },
  { value: 'large', label: 'Large' },
]
const FONT_SIZE_VALUES: Record<string, number> = { small: 1.0, default: 1.0625, large: 1.1875 }

export const TYPE_SCALE_OPTIONS: readonly KnobOption[] = [
  { value: 'compact', label: 'Compact' },
  { value: 'default', label: 'Default' },
  { value: 'dramatic', label: 'Dramatic' },
]
const TYPE_SCALE_VALUES: Record<string, number> = { compact: 1.12, default: 1.18, dramatic: 1.28 }

export const DENSITY_OPTIONS: readonly KnobOption[] = [
  { value: 'compact', label: 'Compact' },
  { value: 'cozy', label: 'Cozy' },
  { value: 'comfortable', label: 'Comfortable' },
]
const DENSITY_FACTORS: Record<string, number> = { compact: 0.85, cozy: 0.92, comfortable: 1.0 }

// Default space scale (rem) — scaled by the density factor.
const SPACE_SCALE: ReadonlyArray<readonly [string, number]> = [
  ['2xs', 0.25],
  ['xs', 0.5],
  ['sm', 0.75],
  ['md', 1],
  ['lg', 1.5],
  ['xl', 2.5],
  ['2xl', 4],
]
// `--text-*` step exponents relative to body (fixed-rem tokens only; the
// clamp-based h2/h1/display stay the theme's so the hero keeps its fluidity).
const TEXT_STEPS: ReadonlyArray<readonly [string, number]> = [
  ['overline', -2],
  ['meta', -1.4],
  ['body-sm', -0.8],
  ['body', 0],
  ['h3', 1],
]

function rem(value: number): string {
  return `${String(Math.round(value * 10000) / 10000)}rem`
}

const HEX_COLOR = /^#[0-9a-fA-F]{3,8}$/

/** Whitelisted value option keys, so JSON from the API can't smuggle anything. */
function isOption(options: readonly KnobOption[], value: string | undefined): value is string {
  return value !== undefined && options.some((option) => option.value === value)
}

/**
 * Resolve stored overrides into a CSS-custom-property style object for the
 * `.nene-public` root. Only known variables with validated values are emitted.
 */
export function resolveOverrideStyle(overrides: ThemeOverrides): Record<string, string> {
  const style: Record<string, string> = {}

  if (typeof overrides.accent === 'string' && HEX_COLOR.test(overrides.accent)) {
    style['--color-accent'] = overrides.accent
    // Keep hover coherent with the chosen accent (slight darken).
    style['--color-accent-hover'] = `color-mix(in oklch, ${overrides.accent}, black 12%)`
  }
  if (isOption(FONT_OPTIONS, overrides.fontBody)) {
    style['--font-sans'] = FONT_STACKS[overrides.fontBody]
  }
  if (isOption(WIDTH_OPTIONS, overrides.contentWidth)) {
    style['--content-w'] = WIDTH_VALUES[overrides.contentWidth]
  }
  if (isOption(GUTTER_OPTIONS, overrides.gutter)) {
    style['--gutter'] = GUTTER_VALUES[overrides.gutter]
  }
  if (isOption(RADIUS_OPTIONS, overrides.radius)) {
    const radius = RADIUS_VALUES[overrides.radius]
    style['--radius-sm'] = radius.sm
    style['--radius-md'] = radius.md
    style['--radius-lg'] = radius.lg
  }

  // Type scale: recompute the fixed-rem text tokens from base × scale.
  const hasFontSize = isOption(FONT_SIZE_OPTIONS, overrides.fontSize)
  const hasTypeScale = isOption(TYPE_SCALE_OPTIONS, overrides.typeScale)
  if (hasFontSize || hasTypeScale) {
    const base = hasFontSize
      ? FONT_SIZE_VALUES[overrides.fontSize as string]
      : FONT_SIZE_VALUES.default
    const scale = hasTypeScale
      ? TYPE_SCALE_VALUES[overrides.typeScale as string]
      : TYPE_SCALE_VALUES.default
    for (const [name, step] of TEXT_STEPS) {
      style[`--text-${name}`] = rem(base * scale ** step)
    }
  }

  // Density: scale the whole space ramp.
  if (isOption(DENSITY_OPTIONS, overrides.density)) {
    const factor = DENSITY_FACTORS[overrides.density]
    for (const [name, value] of SPACE_SCALE) {
      style[`--space-${name}`] = rem(value * factor)
    }
  }

  return style
}

/** Parse the `theme_overrides` setting value (JSON) into a per-theme map. */
export function parseThemeOverrides(raw: string | undefined): Record<string, ThemeOverrides> {
  if (raw === undefined || raw.trim() === '') {
    return {}
  }
  try {
    const parsed: unknown = JSON.parse(raw)
    if (parsed === null || typeof parsed !== 'object') {
      return {}
    }
    return parsed as Record<string, ThemeOverrides>
  } catch {
    return {}
  }
}

type Mode = 'light' | 'dark'

/**
 * Per-mode colour overrides (surface family + text). These MUST be mode-scoped
 * because a light surface would break dark mode and vice-versa. The surface
 * family and muted text are derived from the chosen colours via `color-mix`.
 */
export function resolveModeColors(overrides: ThemeOverrides, mode: Mode): Record<string, string> {
  const style: Record<string, string> = {}

  const surface = overrides.surface?.[mode]
  if (typeof surface === 'string' && HEX_COLOR.test(surface)) {
    style['--color-surface'] = surface
    style['--color-surface-raised'] =
      mode === 'light'
        ? `color-mix(in oklch, ${surface}, white 55%)`
        : `color-mix(in oklch, ${surface}, white 6%)`
    style['--color-surface-overlay'] =
      mode === 'light'
        ? `color-mix(in oklch, ${surface}, black 6%)`
        : `color-mix(in oklch, ${surface}, white 12%)`
    style['--color-surface-sunken'] =
      mode === 'light'
        ? `color-mix(in oklch, ${surface}, black 3%)`
        : `color-mix(in oklch, ${surface}, black 30%)`
    style['--color-border'] =
      mode === 'light'
        ? `color-mix(in oklch, ${surface}, black 16%)`
        : `color-mix(in oklch, ${surface}, white 14%)`
    style['--color-border-strong'] =
      mode === 'light'
        ? `color-mix(in oklch, ${surface}, black 26%)`
        : `color-mix(in oklch, ${surface}, white 24%)`
  }

  const text = overrides.text?.[mode]
  if (typeof text === 'string' && HEX_COLOR.test(text)) {
    style['--color-text-primary'] = text
    style['--color-text-muted'] = `color-mix(in oklch, ${text}, transparent 32%)`
  }

  return style
}

function cssVarsBlock(selector: string, vars: Record<string, string>): string {
  const entries = Object.entries(vars)
  if (entries.length === 0) {
    return ''
  }
  const body = entries.map(([key, value]) => `  ${key}: ${value};`).join('\n')
  return `${selector} {\n${body}\n}\n`
}

/**
 * Build the full override stylesheet for one theme: mode-agnostic vars on both
 * `[data-theme='<id>']` and `-dark`, plus per-mode colours on each. Returns ''
 * when there are no overrides. Values are validated upstream, so the emitted
 * CSS only ever contains known variables and safe values.
 */
export function buildOverrideCss(overrides: ThemeOverrides, themeId: string): string {
  const light = `.nene-public[data-theme='${themeId}']`
  const dark = `.nene-public[data-theme='${themeId}-dark']`

  const agnostic = resolveOverrideStyle(overrides)
  let css = cssVarsBlock(`${light},\n${dark}`, agnostic)
  css += cssVarsBlock(light, resolveModeColors(overrides, 'light'))
  css += cssVarsBlock(dark, resolveModeColors(overrides, 'dark'))
  return css
}

/** Build the override stylesheet for the requested theme from stored JSON. */
export function overrideCssForTheme(raw: string | undefined, themeId: string): string {
  const forTheme = parseThemeOverrides(raw)[themeId]
  return forTheme ? buildOverrideCss(forTheme, themeId) : ''
}

/**
 * Resolve style flags into `data-*` attributes for the `.nene-public` root.
 * Only known flags with allowed enum values are emitted; base CSS implements
 * the actual look (no DOM change). `themeDefaults` are the theme's built-in
 * flags; `overrides` (admin) win.
 */
export function resolveFlagAttrs(
  themeDefaults: ThemeFlags | undefined,
  overrides: ThemeFlags | undefined,
): Record<string, string> {
  const merged: ThemeFlags = { ...themeDefaults, ...overrides }
  const attrs: Record<string, string> = {}
  for (const key of Object.keys(FLAG_DEFS) as Array<keyof ThemeFlags>) {
    const def = FLAG_DEFS[key]
    const value = merged[key]
    if (typeof value === 'string' && def.options.some((option) => option.value === value)) {
      attrs[def.attr] = value
    }
  }
  return attrs
}

/** Resolve flag data-* attributes for a theme from stored overrides JSON. */
export function flagAttrsForTheme(
  raw: string | undefined,
  themeId: string,
  themeDefaults?: ThemeFlags,
): Record<string, string> {
  const forTheme = parseThemeOverrides(raw)[themeId]
  return resolveFlagAttrs(themeDefaults, forTheme?.flags)
}

// Cache the raw overrides JSON so the first paint can apply them synchronously
// (same FOUC-avoidance as the active theme id).
const OVERRIDES_STORAGE_KEY = 'nene_public_theme_overrides'

export function readStoredThemeOverridesRaw(): string {
  if (typeof window === 'undefined') {
    return '{}'
  }
  return window.localStorage.getItem(OVERRIDES_STORAGE_KEY) ?? '{}'
}

export function storeThemeOverridesRaw(raw: string | undefined): void {
  if (typeof window === 'undefined') {
    return
  }
  window.localStorage.setItem(OVERRIDES_STORAGE_KEY, raw ?? '{}')
}
