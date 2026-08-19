import { useEffect, useMemo, useRef } from 'react'
import { publicSettingsToMap, usePublicSettings } from '@/entities/setting'
import { usePublicWidgets } from '@/entities/widget'
import { INLINE_REGION } from '@/shared/lib/resolve-layout'
import { SanitizedHtml } from '@/shared/ui'
import {
  type ResolvedTrustedEmbed,
  parseEmbedAllowlist,
  resolveTrustedEmbed,
} from '../lib/trusted-embed'

export interface InlineTrustedEmbedHtmlProps {
  html: string
  className?: string
}

/** `data-nene-embed="<id>"` — the inert marker, mirroring `TrustedEmbedPlacements::ATTRIBUTE`. */
const MARKER_ATTRIBUTE = 'data-nene-embed'

/** Widget ids are positive integers; anything else is not a placement. */
const MARKER_ID_PATTERN = /^\d{1,18}$/

/**
 * The live SPA twin of `TrustedEmbedPlacements` (#937): renders an `html` field's
 * sanitized body and turns each inline marker inside it —
 * `<div data-nene-embed="12"></div>` — into the actual validated
 * `<script src integrity crossorigin="anonymous" async>`, at that exact spot.
 *
 * This is the twin of the SSR path, and the two must agree rule for rule (the
 * #891 family of bugs is what happens when they drift). Both:
 *
 * - honour the marker on **`div` only**, and only when it is **empty**;
 * - resolve it to a `trusted-embed` widget in the **`inline` region** (a
 *   region-placed widget already renders in its region — honouring a marker for
 *   it would load the same script twice);
 * - re-validate the widget's stored settings against the org's `embed_allowlist`
 *   (origin allowlisted, `src` same-origin, SRI present, `data-*` only) and
 *   render **nothing** if any rule fails;
 * - emit a given widget **at most once** per document.
 *
 * As in `TrustedEmbedWidget`, nothing is ever built from the authored HTML: the
 * markup only chooses *where*, the widget's re-validated settings decide *what*.
 * React does not execute a `<script>` rendered in JSX, so the tag is created
 * imperatively — which is also why the server-rendered copy is inert
 * (`<noscript>`) and cannot double-load.
 */
export function InlineTrustedEmbedHtml({ html, className }: InlineTrustedEmbedHtmlProps) {
  const { data: settings } = usePublicSettings()
  const { data: widgets } = usePublicWidgets()
  const containerRef = useRef<HTMLDivElement>(null)

  // id → resolved embed, for inline-region trusted-embed widgets that pass every
  // rule. Built once per settings/widgets change; the effect only places them.
  const embeds = useMemo(() => {
    const resolved = new Map<number, ResolvedTrustedEmbed>()
    if (settings === undefined || widgets === undefined) {
      return resolved
    }
    const allowlist = parseEmbedAllowlist(publicSettingsToMap(settings.items))
    if (allowlist.length === 0) {
      return resolved
    }
    for (const widget of widgets.items) {
      if (widget.widgetType !== 'trusted-embed' || widget.region !== INLINE_REGION) {
        continue
      }
      const embed = resolveTrustedEmbed(widget.settings, allowlist)
      if (embed !== null) {
        resolved.set(widget.id, embed)
      }
    }
    return resolved
  }, [settings, widgets])

  useEffect(() => {
    const container = containerRef.current
    if (container === null || embeds.size === 0) {
      return
    }

    const injected: HTMLScriptElement[] = []
    const emitted = new Set<number>()

    for (const marker of container.querySelectorAll(`[${MARKER_ATTRIBUTE}]`)) {
      // Twin rules with the SSR pass: `div` only, and empty only. DOMPurify keeps
      // `data-*` on any element, so this check — not the sanitizer — is what makes
      // the two sides agree on what counts as a placement.
      if (marker.tagName !== 'DIV' || marker.childNodes.length > 0) {
        continue
      }

      const raw = marker.getAttribute(MARKER_ATTRIBUTE) ?? ''
      if (!MARKER_ID_PATTERN.test(raw)) {
        continue
      }

      const id = Number(raw)
      if (emitted.has(id)) {
        continue
      }
      emitted.add(id)

      const embed = embeds.get(id)
      if (embed === undefined) {
        continue
      }

      const script = document.createElement('script')
      // Content attributes, so the values are exactly what the browser's SRI +
      // CORS checks read (and what SSR emits), independent of IDL reflection.
      script.setAttribute('src', embed.src)
      script.setAttribute('integrity', embed.integrity)
      script.setAttribute('crossorigin', 'anonymous')
      script.setAttribute('async', '')
      script.async = true
      for (const [name, value] of Object.entries(embed.attributes)) {
        script.setAttribute(name, value)
      }
      marker.appendChild(script)
      injected.push(script)
    }

    return () => {
      for (const script of injected) {
        script.remove()
      }
    }
  }, [embeds, html])

  return (
    <div ref={containerRef}>
      <SanitizedHtml html={html} {...(className !== undefined ? { className } : {})} />
    </div>
  )
}
