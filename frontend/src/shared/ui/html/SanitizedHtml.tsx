import DOMPurify from 'dompurify'
import { useMemo } from 'react'

export interface SanitizedHtmlProps {
  html: string
  className?: string
}

/**
 * Explicit policy — do not lean on shifting library defaults. DOMPurify core
 * strips scripts / `on*` handlers / `javascript:` URLs and the `target`
 * attribute; we additionally forbid global `<style>` blocks (the default keeps
 * them) to enforce the documented contract below. Inline `style` attributes
 * (scoped CSS) are kept.
 */
const SANITIZE_CONFIG = { FORBID_TAGS: ['style'] }

/**
 * Renders author-supplied HTML after sanitizing it. Safe markup and inline
 * `style` attributes (scoped CSS) are kept, but scripts, event handlers (`on*`),
 * `javascript:` URLs and new-tab `target`s are stripped — so an `html` field can
 * carry rich, styled markup without becoming an XSS vector. Global `<style>`
 * blocks and JS are intentionally not supported here; full custom pages that
 * need those use the sandboxed-iframe path (a separate feature), not this
 * component.
 */
export function SanitizedHtml({ html, className }: SanitizedHtmlProps) {
  const clean = useMemo(() => DOMPurify.sanitize(html, SANITIZE_CONFIG), [html])

  if (clean.trim() === '') {
    return null
  }

  return <div className={className} dangerouslySetInnerHTML={{ __html: clean }} />
}
