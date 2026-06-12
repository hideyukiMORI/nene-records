import DOMPurify from 'dompurify'
import { useMemo } from 'react'

export interface SanitizedHtmlProps {
  html: string
  className?: string
}

/**
 * Renders author-supplied HTML after sanitizing it. Safe markup and inline
 * `style` attributes (scoped CSS) are kept, but scripts, event handlers (`on*`)
 * and `javascript:` URLs are stripped — so an `html` field can carry rich,
 * styled markup without becoming an XSS vector. Global `<style>` blocks and JS
 * are intentionally not supported here; full custom pages that need those use
 * the sandboxed-iframe path (a separate feature), not this component.
 */
export function SanitizedHtml({ html, className }: SanitizedHtmlProps) {
  // DOMPurify defaults keep safe tags + inline style attributes and strip
  // scripts / on* handlers / javascript: URLs.
  const clean = useMemo(() => DOMPurify.sanitize(html), [html])

  if (clean.trim() === '') {
    return null
  }

  return <div className={className} dangerouslySetInnerHTML={{ __html: clean }} />
}
