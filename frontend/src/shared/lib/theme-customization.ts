/**
 * Theme customizer (epic #367 / Phase 2 #372).
 *
 * Admins tweak a small set of "knobs" per theme; the values are stored as JSON
 * in the public `theme_overrides` setting and applied as inline CSS custom
 * properties on the public `.nene-public` root (highest specificity, so they
 * override the active theme's tokens). Values are constrained/sanitised here —
 * we only ever emit known CSS variables with validated values, never raw input.
 *
 * Slice 1 knobs: accent (色) / body font (フォント) / content width (幅) /
 * gutter (余白) / radius (角丸). Derived tokens that reference these (e.g.
 * `--color-accent-weak`) auto-follow.
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

/** Resolved inline style for one theme's overrides (empty object if none). */
export function overrideStyleForTheme(
  raw: string | undefined,
  themeId: string,
): Record<string, string> {
  const byTheme = parseThemeOverrides(raw)
  const forTheme = byTheme[themeId]
  return forTheme ? resolveOverrideStyle(forTheme) : {}
}
