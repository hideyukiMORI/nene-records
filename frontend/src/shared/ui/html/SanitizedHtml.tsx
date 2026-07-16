import DOMPurify from 'dompurify'
import { useMemo } from 'react'

export interface SanitizedHtmlProps {
  html: string
  className?: string
}

/**
 * Explicit policy — do not lean on shifting library defaults. DOMPurify core
 * strips scripts / `on*` handlers / `javascript:` URLs; we additionally forbid
 * global `<style>` blocks (the default keeps them). Inline `style` attributes
 * (scoped CSS) are kept.
 *
 * `target` is allowed (#939): the SSR twin (`PublicHtmlSanitizer`, PHP) keeps
 * it, and stripping it here made hydration silently downgrade new-tab links —
 * an SSR/SPA parity break (#891 family). The reverse-tabnabbing surface that
 * justified stripping is closed by the hook below instead, which forces
 * `rel="noopener noreferrer"` onto every anchor that carries a `target`.
 */
const SANITIZE_CONFIG = { FORBID_TAGS: ['style'], ADD_ATTR: ['target'] }

DOMPurify.addHook('afterSanitizeAttributes', (node) => {
  if (node.tagName === 'A' && node.getAttribute('target') !== null) {
    const rel = new Set((node.getAttribute('rel') ?? '').split(/\s+/).filter(Boolean))
    rel.add('noopener')
    rel.add('noreferrer')
    node.setAttribute('rel', [...rel].join(' '))
  }
})

/**
 * Renders author-supplied HTML after sanitizing it. Safe markup, inline
 * `style` attributes (scoped CSS) and new-tab `target`s (with `noopener
 * noreferrer` enforced) are kept, but scripts, event handlers (`on*`) and
 * `javascript:` URLs are stripped — so an `html` field can carry rich, styled
 * markup without becoming an XSS vector. Global `<style>` blocks and JS are
 * intentionally not supported here; full custom pages that need those use the
 * sandboxed-iframe path (a separate feature), not this component.
 */
export function SanitizedHtml({ html, className }: SanitizedHtmlProps) {
  const clean = useMemo(() => DOMPurify.sanitize(html, SANITIZE_CONFIG), [html])

  if (clean.trim() === '') {
    return null
  }

  return <div className={className} dangerouslySetInnerHTML={{ __html: clean }} />
}
