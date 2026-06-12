export interface SandboxedBundleProps {
  /** A full HTML document (may include <style> and <script>). */
  html: string
  className?: string
  title?: string
}

/**
 * Renders an author-supplied HTML/JS/CSS bundle inside a sandboxed iframe.
 *
 * The iframe uses `sandbox="allow-scripts"` WITHOUT `allow-same-origin`, so its
 * content runs in an opaque origin: scripts execute but cannot reach the parent
 * DOM, cookies, localStorage, or the app's API session. This is what makes it
 * safe to accept arbitrary HTML/JS/CSS (e.g. from an external design tool) — the
 * isolation, not sanitization, is the security boundary.
 *
 * Auto-height (postMessage) and richer data binding are intentionally out of
 * scope here; a fixed min-height is used.
 */
export function SandboxedBundle({
  html,
  className,
  title = 'Custom content',
}: SandboxedBundleProps) {
  if (html.trim() === '') {
    return null
  }

  return (
    <iframe
      title={title}
      srcDoc={html}
      sandbox="allow-scripts"
      loading="lazy"
      style={{ height: 600 }}
      className={className ?? 'w-full border-0'}
    />
  )
}
