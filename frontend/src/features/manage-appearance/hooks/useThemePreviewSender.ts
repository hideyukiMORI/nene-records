import { useCallback, useEffect, useMemo, useRef } from 'react'
import {
  flagAttrsForTheme,
  overrideCssForTheme,
  type ThemeOverrides,
} from '@/shared/lib/theme-customization'
import { THEME_PREVIEW_APPLY, THEME_PREVIEW_READY } from '@/shared/lib/theme-preview-protocol'

/**
 * Customizer side of the live theme preview (#538 ②). Owns the preview iframe
 * ref and posts the current draft (token-override CSS + flag attrs, built with
 * the same helpers the public shell uses) to it — on every draft change and
 * whenever the iframe announces it is ready (covers the mount race).
 */
export function useThemePreviewSender(themeId: string, draft: ThemeOverrides) {
  const iframeRef = useRef<HTMLIFrameElement>(null)

  const raw = useMemo(() => JSON.stringify({ [themeId]: draft }), [themeId, draft])
  const overrideCss = useMemo(() => overrideCssForTheme(raw, themeId), [raw, themeId])
  const flagAttrs = useMemo(() => flagAttrsForTheme(raw, themeId), [raw, themeId])

  const post = useCallback((): void => {
    const win = iframeRef.current?.contentWindow

    if (win === null || win === undefined) {
      return
    }

    win.postMessage({ type: THEME_PREVIEW_APPLY, overrideCss, flagAttrs }, window.location.origin)
  }, [overrideCss, flagAttrs])

  // Push the draft whenever the computed CSS / flags change.
  useEffect(() => {
    post()
  }, [post])

  // Re-push when the iframe (re)mounts and announces readiness.
  useEffect(() => {
    const onMessage = (event: MessageEvent): void => {
      const data = event.data as { type?: string } | null

      if (event.origin === window.location.origin && data?.type === THEME_PREVIEW_READY) {
        post()
      }
    }

    window.addEventListener('message', onMessage)

    return () => {
      window.removeEventListener('message', onMessage)
    }
  }, [post])

  return { iframeRef }
}
