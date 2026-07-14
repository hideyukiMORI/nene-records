import { useEffect, useMemo, useRef } from 'react'
import { usePublicSettings } from '@/entities/setting'
import { publicSettingsToMap } from '@/entities/setting'
import type { Widget } from '@/entities/widget'
import { parseEmbedAllowlist, resolveTrustedEmbed } from '../lib/trusted-embed'

export interface TrustedEmbedWidgetProps {
  widget: Widget
}

/**
 * Live renderer for the `trusted-embed` widget (#802). Re-validates the widget's
 * settings against the org's `embed_allowlist` (origin on the allowlist, `src`
 * same-origin, SRI required, data-* attributes only) — the exact same gate the
 * SSR path (`TrustedEmbedScripts`) applies — and only then injects the actual
 * `<script src integrity crossorigin="anonymous" async>`.
 *
 * React does not execute a `<script>` rendered in JSX, so the tag is created
 * imperatively into a container ref. An embed that fails any rule renders
 * nothing (the server CSP is the other layer of defense).
 */
export function TrustedEmbedWidget({ widget }: TrustedEmbedWidgetProps) {
  const { data } = usePublicSettings()
  const containerRef = useRef<HTMLDivElement>(null)

  const resolved = useMemo(() => {
    if (data === undefined) {
      return null
    }
    const allowlist = parseEmbedAllowlist(publicSettingsToMap(data.items))
    return resolveTrustedEmbed(widget.settings, allowlist)
  }, [data, widget.settings])

  useEffect(() => {
    const container = containerRef.current
    if (container === null || resolved === null) {
      return
    }

    const script = document.createElement('script')
    // Set as content attributes so the values are exactly what the browser's SRI
    // + CORS checks read (and what SSR emits), independent of IDL reflection.
    script.setAttribute('src', resolved.src)
    script.setAttribute('integrity', resolved.integrity)
    script.setAttribute('crossorigin', 'anonymous')
    script.setAttribute('async', '')
    script.async = true
    for (const [name, value] of Object.entries(resolved.attributes)) {
      script.setAttribute(name, value)
    }
    container.appendChild(script)

    return () => {
      container.removeChild(script)
    }
  }, [resolved])

  if (resolved === null) {
    return null
  }

  return <div ref={containerRef} className="trusted-embed" />
}
