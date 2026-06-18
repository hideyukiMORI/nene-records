/**
 * Public-site header content (#419 Phase C).
 *
 * The Top bar (phone / email / free text) and CTA button (label + URL) are
 * **site content**, stored as JSON in the public `header_config` setting (the
 * structural skeleton/visibility lives in `theme_overrides` style flags). The
 * public shell reads this and renders the Top bar row + CTA.
 *
 * Free text is rendered as React text children (auto-escaped) — no HTML, so no
 * sanitisation library is needed. CTA URLs are constrained by `safeHref` to
 * block `javascript:`/`data:` and similar script-bearing schemes.
 */

export interface HeaderTopbar {
  enabled: boolean
  /** Phone number; rendered as a `tel:` link when present. */
  phone: string
  /** Email; rendered as a `mailto:` link when present. */
  email: string
  /** Free text (hours / address / announcement). Plain text only. */
  infoText: string
}

export interface HeaderCta {
  enabled: boolean
  label: string
  /** Destination URL (validated by `safeHref` at render). */
  url: string
}

export interface HeaderConfig {
  topbar: HeaderTopbar
  cta: HeaderCta
}

export const DEFAULT_HEADER_CONFIG: HeaderConfig = {
  topbar: { enabled: false, phone: '', email: '', infoText: '' },
  cta: { enabled: false, label: '', url: '' },
}

/** Schemes safe to put in an href without script-execution risk. */
const SAFE_SCHEMES = ['http:', 'https:', 'mailto:', 'tel:']

/**
 * Return a safe href, or '' if the URL could execute script. Allows absolute
 * http(s)/mailto/tel URLs and site-relative paths (`/`, `#`, `?`); rejects
 * `javascript:`, `data:`, `vbscript:`, etc.
 */
export function safeHref(url: string): string {
  const trimmed = url.trim()
  if (trimmed === '') {
    return ''
  }
  // Site-relative / fragment / query links carry no scheme — always safe.
  if (/^[/#?]/.test(trimmed)) {
    return trimmed
  }
  const match = /^([a-z][a-z0-9+.-]*:)/i.exec(trimmed)
  if (match === null) {
    // No scheme and not clearly relative (e.g. "example.com/x") — treat as https.
    return `https://${trimmed}`
  }
  return SAFE_SCHEMES.includes(match[1].toLowerCase()) ? trimmed : ''
}

function asString(value: unknown): string {
  return typeof value === 'string' ? value : ''
}

function asBool(value: unknown): boolean {
  return value === true
}

/** Parse the stored `header_config` JSON defensively into a full config. */
export function parseHeaderConfig(raw: string | undefined): HeaderConfig {
  if (raw === undefined || raw.trim() === '') {
    return DEFAULT_HEADER_CONFIG
  }
  let data: unknown
  try {
    data = JSON.parse(raw)
  } catch {
    return DEFAULT_HEADER_CONFIG
  }
  if (typeof data !== 'object' || data === null) {
    return DEFAULT_HEADER_CONFIG
  }
  const record = data as Record<string, unknown>
  const topbar = (record.topbar ?? {}) as Record<string, unknown>
  const cta = (record.cta ?? {}) as Record<string, unknown>
  return {
    topbar: {
      enabled: asBool(topbar.enabled),
      phone: asString(topbar.phone),
      email: asString(topbar.email),
      infoText: asString(topbar.infoText),
    },
    cta: {
      enabled: asBool(cta.enabled),
      label: asString(cta.label),
      url: asString(cta.url),
    },
  }
}

/** Serialise a config back to the stored JSON string. */
export function serializeHeaderConfig(config: HeaderConfig): string {
  return JSON.stringify(config)
}

/** True when the Top bar is enabled and has at least one piece of content. */
export function hasTopbarContent(topbar: HeaderTopbar): boolean {
  return topbar.enabled && (topbar.phone !== '' || topbar.email !== '' || topbar.infoText !== '')
}

/** True when the CTA is enabled and resolves to a safe, labelled link. */
export function hasCta(cta: HeaderCta): boolean {
  return cta.enabled && cta.label.trim() !== '' && safeHref(cta.url) !== ''
}
