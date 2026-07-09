/**
 * Public-site footer content (#766 — footer 検証レポート Phase 2).
 *
 * The social icon links, the legal link bar (privacy / terms / 特商法 …), and
 * the Powered-by visibility are **site content**, stored as JSON in the public
 * `footer_config` setting — the typed sibling of `header_config` (#419). The
 * public shell renders the bottom bar from this; free-form HTML is deliberately
 * not supported (typed elements only).
 *
 * URLs are constrained by `safeHref` (header-config) at render to block
 * `javascript:`/`data:` and similar script-bearing schemes.
 */

/** Platforms with a bundled first-party icon (no external assets). */
export const SOCIAL_PLATFORMS = [
  'x',
  'instagram',
  'facebook',
  'youtube',
  'linkedin',
  'github',
  'tiktok',
  'line',
  'website',
] as const

export type SocialPlatform = (typeof SOCIAL_PLATFORMS)[number]

export interface FooterSocialLink {
  platform: SocialPlatform
  url: string
}

export interface FooterLegalLink {
  label: string
  url: string
}

export interface FooterConfig {
  /** Social icon links, rendered in the bottom bar (right side). */
  social: FooterSocialLink[]
  /** Inline legal/utility links next to the copyright (privacy, terms, 特商法 …). */
  legalLinks: FooterLegalLink[]
  /** Show the "Powered by NENE2" note in the bottom bar. */
  showPoweredBy: boolean
}

export const DEFAULT_FOOTER_CONFIG: FooterConfig = {
  social: [],
  legalLinks: [],
  showPoweredBy: true,
}

function asString(value: unknown): string {
  return typeof value === 'string' ? value : ''
}

function isSocialPlatform(value: unknown): value is SocialPlatform {
  return typeof value === 'string' && (SOCIAL_PLATFORMS as readonly string[]).includes(value)
}

/** Parse the stored `footer_config` JSON defensively into a full config. */
export function parseFooterConfig(raw: string | undefined | null): FooterConfig {
  if (raw === undefined || raw === null || raw.trim() === '') {
    return DEFAULT_FOOTER_CONFIG
  }

  let parsed: unknown
  try {
    parsed = JSON.parse(raw)
  } catch {
    return DEFAULT_FOOTER_CONFIG
  }
  if (typeof parsed !== 'object' || parsed === null) {
    return DEFAULT_FOOTER_CONFIG
  }

  const record = parsed as Record<string, unknown>

  const social: FooterSocialLink[] = Array.isArray(record.social)
    ? record.social.flatMap((entry): FooterSocialLink[] => {
        if (typeof entry !== 'object' || entry === null) return []
        const item = entry as Record<string, unknown>
        const url = asString(item.url)
        if (!isSocialPlatform(item.platform) || url === '') return []
        return [{ platform: item.platform, url }]
      })
    : []

  const legalLinks: FooterLegalLink[] = Array.isArray(record.legalLinks)
    ? record.legalLinks.flatMap((entry): FooterLegalLink[] => {
        if (typeof entry !== 'object' || entry === null) return []
        const item = entry as Record<string, unknown>
        const label = asString(item.label)
        const url = asString(item.url)
        if (label === '' || url === '') return []
        return [{ label, url }]
      })
    : []

  return {
    social,
    legalLinks,
    showPoweredBy: record.showPoweredBy !== false,
  }
}

export function serializeFooterConfig(config: FooterConfig): string {
  return JSON.stringify(config)
}
