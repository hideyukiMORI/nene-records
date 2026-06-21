/**
 * Heuristic SVG danger checks for the theme-bundle CI gate (#373).
 *
 * This gives theme authors fast feedback when a `data:image/svg+xml` URI inside
 * components.css carries active content. It is a quality gate, NOT the trust
 * boundary: the authoritative deep sanitiser runs server-side on upload
 * (`src/Media/SvgSanitizer.php`) and at serve time (nosniff + locked-down CSP).
 */

const SVG_DANGER: { re: RegExp; message: string }[] = [
  { re: /<script[\s>/]/i, message: 'data URI SVG contains <script>' },
  { re: /<foreignobject[\s>/]/i, message: 'data URI SVG contains <foreignObject>' },
  { re: /\son[a-z]+\s*=/i, message: 'data URI SVG contains an on* event handler' },
  { re: /javascript:/i, message: 'data URI SVG contains a javascript: URI' },
  {
    re: /(?:xlink:href|href)\s*=\s*['"]?\s*(?:https?:|\/\/|data:)/i,
    message: 'data URI SVG contains an external/data href',
  },
]

/** Dangerous-construct messages for a decoded SVG string. */
export function findSvgIssues(svg: string): string[] {
  return SVG_DANGER.filter(({ re }) => re.test(svg)).map(({ message }) => message)
}

function decodeBase64(value: string): string | null {
  try {
    return atob(value.replace(/\s+/g, ''))
  } catch {
    return null
  }
}

/**
 * Inspect a CSS declaration value for `data:image/svg+xml` URIs and report any
 * dangerous constructs in the (percent- or base64-) decoded payload.
 */
export function findDataUriSvgIssues(cssValue: string): string[] {
  if (!/data:image\/svg\+xml/i.test(cssValue)) {
    return []
  }

  const candidates: string[] = []

  // Percent / plain encoding — decode the whole value so attribute quotes don't
  // truncate the payload.
  try {
    candidates.push(decodeURIComponent(cssValue))
  } catch {
    candidates.push(cssValue)
  }

  // Base64 payloads.
  const base64 = /base64,([a-z0-9+/=\s]+)/gi
  let match: RegExpExecArray | null
  while ((match = base64.exec(cssValue)) !== null) {
    const decoded = decodeBase64(match[1] ?? '')
    if (decoded !== null) {
      candidates.push(decoded)
    }
  }

  const found = new Set<string>()
  for (const candidate of candidates) {
    for (const message of findSvgIssues(candidate)) {
      found.add(message)
    }
  }

  return [...found]
}
