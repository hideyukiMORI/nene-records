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

export interface FloatingCtaConditions {
  /** Entity type slugs the CTA shows on; empty = all types. */
  types: string[]
  /** Path globs (`*` wildcard) to include; empty = all paths. */
  urlGlobs: string[]
  /** Path globs to exclude (wins over include). */
  exclude: string[]
}

export interface FloatingCtaConfig {
  enabled: boolean
  position: FloatingCtaPosition
  /** `#RRGGBB` accent, or '' to use the built-in default. */
  accent: string
  content: { icon: string; label: string; sub: string }
  link: { url: string; newTab: boolean }
  conditions: FloatingCtaConditions
}

export const DEFAULT_FLOATING_CTA: FloatingCtaConfig = {
  enabled: false,
  position: 'br',
  accent: '',
  content: { icon: '', label: '', sub: '' },
  link: { url: '', newTab: true },
  conditions: { types: [], urlGlobs: [], exclude: [] },
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
