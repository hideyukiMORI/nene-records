/**
 * Public-site floating CTA (#982 P1).
 *
 * A first-party, org-scoped floating call-to-action button rendered by the public
 * SSR shell as site chrome (see backend `FloatingCta`/`FloatingCtaHtml`). Stored as
 * JSON in the public `floating_cta` setting. The server is the trust boundary
 * (`FloatingCtaValidator`, fail-closed); this module is the admin-side draft shape and
 * the same href scheme allowlist mirrored for parity (reuses {@link safeHref}).
 *
 * P1: structured content only (emoji icon + label + sub), position presets `br`/`bl`,
 * trigger `always`, condition matching by entity type / URL glob. Images, raw HTML,
 * multiple buttons and scroll/delay triggers are P2/P3.
 */

import { safeHref } from '@/shared/lib/header-config'

export type FloatingCtaPosition = 'br' | 'bl'

/** When the FAB appears: immediately, or after a delay (#982 P2 d). `scroll` is reserved. */
export type FloatingCtaTrigger = 'always' | 'delay'

/** Delay-trigger bounds in seconds (#982 P2 d). */
export const MAX_FLOATING_CTA_DELAY_SECONDS = 60

export interface FloatingCtaConditions {
  /** Entity type slugs the CTA shows on; empty = all types. */
  types: string[]
  /** Path globs (`*` wildcard) to include; empty = all paths. */
  urlGlobs: string[]
  /** Path globs to exclude (wins over include). */
  exclude: string[]
}

/** Upper bound for the page-bottom clearance reserved for the FAB (#982 P2 (c)). */
export const MAX_FLOATING_CTA_BOTTOM_OFFSET = 400

export interface FloatingCtaConfig {
  enabled: boolean
  position: FloatingCtaPosition
  /** `#RRGGBB` accent, or '' to use the built-in default. */
  accent: string
  content: { icon: string; iconId: string; label: string; sub: string }
  link: { url: string; newTab: boolean }
  conditions: FloatingCtaConditions
  /** Extra clearance (px) reserved at the page bottom so the fixed FAB never covers footer content; 0 = none. */
  bottomOffset: number
  /** When true, the FAB shows a "×" and remembers dismissal in localStorage (needs a CSP nonce'd script). */
  dismissible: boolean
  /** When the FAB appears. */
  trigger: FloatingCtaTrigger
  /** Delay in seconds for the `delay` trigger (1–60); ignored for `always`. */
  triggerValue: number
}

export const DEFAULT_FLOATING_CTA: FloatingCtaConfig = {
  enabled: false,
  position: 'br',
  accent: '',
  content: { icon: '', iconId: '', label: '', sub: '' },
  link: { url: '', newTab: true },
  conditions: { types: [], urlGlobs: [], exclude: [] },
  bottomOffset: 0,
  dismissible: false,
  trigger: 'always',
  triggerValue: 0,
}

/** Re-exported so the CTA editor shares the exact header-CTA scheme allowlist. */
export { safeHref }

function asString(value: unknown): string {
  return typeof value === 'string' ? value : ''
}

function asBool(value: unknown, fallback = false): boolean {
  return typeof value === 'boolean' ? value : fallback
}

function asStringList(value: unknown): string[] {
  if (!Array.isArray(value)) {
    return []
  }
  return value
    .filter((item): item is string => typeof item === 'string' && item.trim() !== '')
    .map((item) => item.trim())
}

function asPosition(value: unknown): FloatingCtaPosition {
  return value === 'bl' ? 'bl' : 'br'
}

/** Clamp a stored bottom offset to a valid, non-negative px within bounds (#982 P2 (c)). */
function asBottomOffset(value: unknown): number {
  return typeof value === 'number' && Number.isInteger(value) && value >= 0
    ? Math.min(value, MAX_FLOATING_CTA_BOTTOM_OFFSET)
    : 0
}

function asTrigger(value: unknown): FloatingCtaTrigger {
  return value === 'delay' ? 'delay' : 'always'
}

/** Delay seconds are only meaningful for the `delay` trigger; clamp to 1–60 there, else 0. */
function asTriggerValue(trigger: FloatingCtaTrigger, value: unknown): number {
  if (trigger !== 'delay' || typeof value !== 'number' || !Number.isInteger(value)) {
    return trigger === 'delay' ? 1 : 0
  }
  return Math.max(1, Math.min(value, MAX_FLOATING_CTA_DELAY_SECONDS))
}

/** Parse the stored `floating_cta` JSON defensively into a full config. */
export function parseFloatingCta(raw: string | undefined): FloatingCtaConfig {
  if (raw === undefined || raw.trim() === '') {
    return DEFAULT_FLOATING_CTA
  }
  let data: unknown
  try {
    data = JSON.parse(raw)
  } catch {
    return DEFAULT_FLOATING_CTA
  }
  if (typeof data !== 'object' || data === null || Array.isArray(data)) {
    return DEFAULT_FLOATING_CTA
  }
  const record = data as Record<string, unknown>
  const content = (record.content ?? {}) as Record<string, unknown>
  const link = (record.link ?? {}) as Record<string, unknown>
  const conditions = (record.conditions ?? {}) as Record<string, unknown>
  return {
    enabled: asBool(record.enabled),
    position: asPosition(record.position),
    accent: /^#[0-9A-Fa-f]{6}$/.test(asString(record.accent)) ? asString(record.accent) : '',
    content: {
      icon: asString(content.icon),
      iconId: asString(content.iconId),
      label: asString(content.label),
      sub: asString(content.sub),
    },
    link: {
      url: asString(link.url),
      newTab: asBool(link.newTab, true),
    },
    conditions: {
      types: asStringList(conditions.types),
      urlGlobs: asStringList(conditions.urlGlobs),
      exclude: asStringList(conditions.exclude),
    },
    bottomOffset: asBottomOffset(record.bottomOffset),
    dismissible: asBool(record.dismissible),
    trigger: asTrigger(record.trigger),
    triggerValue: asTriggerValue(asTrigger(record.trigger), record.triggerValue),
  }
}

/** Serialise a config back to the stored JSON string. */
export function serializeFloatingCta(config: FloatingCtaConfig): string {
  return JSON.stringify(config)
}

/** Split a comma / newline separated string into a trimmed, non-empty list. */
export function parseList(text: string): string[] {
  return text
    .split(/[\n,]/)
    .map((item) => item.trim())
    .filter((item) => item !== '')
}

/** Join a list into a comma-separated string for a text field. */
export function joinList(list: string[]): string {
  return list.join(', ')
}

/** True when the CTA is enabled and resolves to a safe, labelled link. */
export function isFloatingCtaRenderable(config: FloatingCtaConfig): boolean {
  return config.enabled && config.content.label.trim() !== '' && safeHref(config.link.url) !== ''
}

/**
 * Curated first-party SVG icons for the floating CTA picker (#982 P2). The ids and
 * markup mirror the backend `FloatingCtaIcons` (the server is the validation authority);
 * this copy powers the admin picker preview. `svg` is our own constant markup — safe to
 * render (no org input).
 */
export const FLOATING_CTA_ICONS: ReadonlyArray<{ id: string; svg: string }> = [
  {
    id: 'calendar',
    svg: wrap(
      '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
    ),
  },
  {
    id: 'video',
    svg: wrap('<rect x="2" y="6" width="13" height="12" rx="2"/><path d="M22 8.5 15 12l7 3.5z"/>'),
  },
  {
    id: 'chat',
    svg: wrap('<path d="M21 11.5a8.5 8.5 0 0 1-12.3 7.6L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5z"/>'),
  },
  {
    id: 'mail',
    svg: wrap('<rect x="2" y="4.5" width="20" height="15" rx="2"/><path d="m3 6 9 7 9-7"/>'),
  },
  {
    id: 'phone',
    svg: wrap(
      '<path d="M6.6 3H4a1.9 1.9 0 0 0-1.9 2.1 16.9 16.9 0 0 0 14.8 14.8A1.9 1.9 0 0 0 19 18v-2.6a1.3 1.3 0 0 0-1-1.3l-3-.6a1.3 1.3 0 0 0-1.2.4l-1 1a13 13 0 0 1-5-5l1-1a1.3 1.3 0 0 0 .4-1.3l-.6-3A1.3 1.3 0 0 0 6.6 3z"/>',
    ),
  },
  { id: 'clock', svg: wrap('<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>') },
  {
    id: 'sparkle',
    svg: wrap('<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/>'),
  },
  { id: 'arrow-right', svg: wrap('<path d="M4 12h15M13 6l6 6-6 6"/>') },
]

function wrap(inner: string): string {
  return (
    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"' +
    ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    inner +
    '</svg>'
  )
}
