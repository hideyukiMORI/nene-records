/**
 * Bundle field model (#311 / #491 WS3-S3a).
 *
 * A `bundle` field is the "fully custom page" escape hatch: a self-contained
 * HTML/JS/CSS document rendered ONLY inside a sandboxed iframe. Because iframe
 * content is invisible to crawlers and assistive tech, the value is a small
 * envelope carrying a crawlable text twin (`seoText`, markdown) — the dual-
 * representation contract. The server (`BundleField/BundleDocumentValidator.php`)
 * is the trust boundary; this mirrors it for the editor + consumer.
 *
 * Lenient parse: a legacy raw-HTML string (pre-envelope) is read as
 * `{ html, seoText: '' }` so old values still render.
 */
export interface BundleDocument {
  html: string
  seoText: string
}

export function parseBundleDocument(raw: string): BundleDocument {
  if (raw.trim() === '') {
    return { html: '', seoText: '' }
  }
  try {
    const decoded: unknown = JSON.parse(raw)
    if (typeof decoded === 'object' && decoded !== null && !Array.isArray(decoded)) {
      const record = decoded as Record<string, unknown>
      return {
        html: typeof record.html === 'string' ? record.html : '',
        seoText: typeof record.seoText === 'string' ? record.seoText : '',
      }
    }
  } catch {
    // Not JSON → a legacy raw-HTML bundle.
  }
  return { html: raw, seoText: '' }
}

export function serializeBundleDocument(doc: BundleDocument): string {
  return JSON.stringify({ html: doc.html, seoText: doc.seoText })
}
