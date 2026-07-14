/**
 * Client-side gate for the `trusted-embed` widget (#802), mirroring the backend
 * `TrustedEmbedSettings` / `EmbedAllowlist` rules so the SPA renderer applies the
 * exact same allowlist + origin + SRI checks as SSR. Nothing here trusts the
 * stored settings: an embed that fails any rule resolves to `null` and renders
 * nothing (defense in depth alongside the server-set CSP).
 */

/** `https://` + dotted host (labels a-z0-9, hyphen-internal) + optional port; nothing after the authority. */
const ORIGIN_PATTERN =
  /^https:\/\/[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(:\d{1,5})?$/

/** One or more space-separated SRI hashes: `sha(256|384|512)-<base64>`. */
const INTEGRITY_PATTERN =
  /^(sha(256|384|512)-[A-Za-z0-9+/]+={0,2})( sha(256|384|512)-[A-Za-z0-9+/]+={0,2})*$/

/** `data-` + lowercase letters/digits/hyphen. */
const DATA_ATTRIBUTE_PATTERN = /^data-[a-z0-9-]+$/

const MAX_ATTRIBUTES = 20
const MAX_ORIGINS = 10

/**
 * The org's trusted-embed allowlist, parsed from the public `embed_allowlist`
 * setting (a JSON array of https origins). Malformed / duplicate entries are
 * dropped, mirroring the backend `EmbedAllowlist`.
 */
export function parseEmbedAllowlist(settings: Record<string, string>): string[] {
  const raw = (settings['embed_allowlist'] ?? '').trim()
  if (raw === '') {
    return []
  }

  let decoded: unknown
  try {
    decoded = JSON.parse(raw)
  } catch {
    return []
  }
  if (!Array.isArray(decoded)) {
    return []
  }

  const origins: string[] = []
  for (const entry of decoded) {
    if (typeof entry !== 'string') {
      continue
    }
    const origin = entry.trim().toLowerCase()
    if (!ORIGIN_PATTERN.test(origin)) {
      continue
    }
    if (!origins.includes(origin)) {
      origins.push(origin)
    }
    if (origins.length >= MAX_ORIGINS) {
      break
    }
  }
  return origins
}

export interface ResolvedTrustedEmbed {
  origin: string
  src: string
  integrity: string
  attributes: Record<string, string>
}

/** The scheme+host+port origin of an https URL, or `null` when malformed. */
function originOf(url: string): string | null {
  let parsed: URL
  try {
    parsed = new URL(url)
  } catch {
    return null
  }
  if (parsed.protocol !== 'https:') {
    return null
  }
  if (parsed.username !== '' || parsed.password !== '') {
    return null
  }
  if (parsed.hostname === '') {
    return null
  }
  return parsed.port !== ''
    ? `https://${parsed.hostname.toLowerCase()}:${parsed.port}`
    : `https://${parsed.hostname.toLowerCase()}`
}

/**
 * Validate a trusted-embed widget's settings against the allowlist. Returns the
 * resolved embed when every rule holds — origin is an explicit https origin that
 * is on the allowlist, `src`'s origin equals it, SRI is present and well-formed,
 * attributes are `data-*` strings only — otherwise `null`.
 */
export function resolveTrustedEmbed(
  settings: Record<string, unknown>,
  allowlist: string[],
): ResolvedTrustedEmbed | null {
  const rawOrigin = settings['origin']
  const rawSrc = settings['src']
  const rawIntegrity = settings['integrity']

  const origin = typeof rawOrigin === 'string' ? rawOrigin.trim().toLowerCase() : ''
  const src = typeof rawSrc === 'string' ? rawSrc.trim() : ''
  const integrity = typeof rawIntegrity === 'string' ? rawIntegrity.trim() : ''

  if (!ORIGIN_PATTERN.test(origin)) {
    return null
  }
  if (!allowlist.includes(origin)) {
    return null
  }
  if (originOf(src) !== origin) {
    return null
  }
  if (!INTEGRITY_PATTERN.test(integrity)) {
    return null
  }

  const attributes: Record<string, string> = {}
  const rawAttributes = settings['attributes']
  if (rawAttributes !== undefined && rawAttributes !== null) {
    if (typeof rawAttributes !== 'object' || Array.isArray(rawAttributes)) {
      return null
    }
    const entries = Object.entries(rawAttributes as Record<string, unknown>)
    if (entries.length > MAX_ATTRIBUTES) {
      return null
    }
    for (const [name, value] of entries) {
      const lower = name.toLowerCase()
      if (!DATA_ATTRIBUTE_PATTERN.test(lower)) {
        return null
      }
      if (typeof value !== 'string') {
        return null
      }
      attributes[lower] = value
    }
  }

  return { origin, src, integrity, attributes }
}
