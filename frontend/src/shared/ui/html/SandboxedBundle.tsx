import { useEffect, useRef, useState } from 'react'

export interface SandboxedBundleProps {
  /** A full HTML document (may include <style> and <script>). */
  html: string
  className?: string
  title?: string
}

const HEIGHT_MESSAGE_TYPE = 'nene:bundle-height'
const INITIAL_HEIGHT = 400
const MAX_HEIGHT = 20000

/**
 * Script injected into the iframe document so it reports its content height to
 * the parent. Authors don't need to add anything — we append this to their
 * bundle. It posts to the opaque-origin parent via postMessage (which works
 * cross-origin); the parent validates the source before trusting it.
 */
const HEIGHT_REPORTER = `<script>(function(){function r(){try{parent.postMessage({type:'${HEIGHT_MESSAGE_TYPE}',height:document.documentElement.scrollHeight},'*');}catch(e){}}window.addEventListener('load',r);if(window.ResizeObserver){new ResizeObserver(r).observe(document.documentElement);}else{window.addEventListener('resize',r);}r();})();</script>`

function readReportedHeight(event: MessageEvent): number | null {
  const data: unknown = event.data
  if (typeof data !== 'object' || data === null) {
    return null
  }
  const record = data as Record<string, unknown>
  if (record['type'] !== HEIGHT_MESSAGE_TYPE) {
    return null
  }
  const raw = record['height']
  const height = typeof raw === 'number' ? raw : Number(raw)
  return Number.isFinite(height) && height > 0 ? height : null
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
 * The iframe auto-resizes to its content height via a postMessage handshake
 * (see HEIGHT_REPORTER); the parent only trusts messages from this iframe's
 * own window.
 */
export function SandboxedBundle({
  html,
  className,
  title = 'Custom content',
}: SandboxedBundleProps) {
  const iframeRef = useRef<HTMLIFrameElement>(null)
  const [height, setHeight] = useState(INITIAL_HEIGHT)

  useEffect(() => {
    function onMessage(event: MessageEvent): void {
      const iframe = iframeRef.current
      // Only trust height reports coming from this iframe's own window.
      if (iframe === null || event.source !== iframe.contentWindow) {
        return
      }
      const reported = readReportedHeight(event)
      if (reported !== null) {
        setHeight(Math.min(reported, MAX_HEIGHT))
      }
    }

    window.addEventListener('message', onMessage)
    return () => {
      window.removeEventListener('message', onMessage)
    }
  }, [])

  if (html.trim() === '') {
    return null
  }

  return (
    <iframe
      ref={iframeRef}
      title={title}
      srcDoc={`${html}${HEIGHT_REPORTER}`}
      sandbox="allow-scripts"
      loading="lazy"
      style={{ height }}
      className={className ?? 'w-full border-0'}
    />
  )
}
