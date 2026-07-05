import { useEffect, useState } from 'react'
import type { ThemeLogo } from '@/shared/lib/theme-customization'
import {
  isThemePreviewRequest,
  THEME_PREVIEW_APPLY,
  THEME_PREVIEW_READY,
  type ThemePreviewApplyMessage,
} from '@/shared/lib/theme-preview-protocol'

interface ThemePreviewState {
  overrideCss: string
  flagAttrs: Record<string, string>
  themeLogo: ThemeLogo
}

function readThemeLogo(value: ThemeLogo | undefined): ThemeLogo {
  return {
    light: typeof value?.light === 'string' ? value.light : undefined,
    dark: typeof value?.dark === 'string' ? value.dark : undefined,
  }
}

interface ThemePreviewBridge {
  /** This document is an embedded theme-preview surface (admin customizer). */
  isPreview: boolean
  /** The latest draft pushed by the customizer (null until the first message). */
  preview: ThemePreviewState | null
}

function inIframePreview(): boolean {
  return (
    typeof window !== 'undefined' &&
    window.parent !== window &&
    isThemePreviewRequest(window.location.search)
  )
}

/**
 * Public-shell side of the live theme preview (#538 ②). When the page is the
 * customizer's preview iframe, it announces readiness to the parent and then
 * applies each draft theme it receives (origin-checked). The public shell swaps
 * its saved override CSS / flag attrs for these while previewing.
 */
export function useThemePreviewBridge(): ThemePreviewBridge {
  const isPreview = inIframePreview()
  const [preview, setPreview] = useState<ThemePreviewState | null>(null)

  useEffect(() => {
    if (!isPreview) {
      return
    }

    const onMessage = (event: MessageEvent): void => {
      if (event.origin !== window.location.origin) {
        return
      }

      const data = event.data as Partial<ThemePreviewApplyMessage> | null

      if (data?.type === THEME_PREVIEW_APPLY) {
        setPreview({
          overrideCss: typeof data.overrideCss === 'string' ? data.overrideCss : '',
          flagAttrs: data.flagAttrs ?? {},
          themeLogo: readThemeLogo(data.themeLogo),
        })
      }
    }

    window.addEventListener('message', onMessage)
    // Tell the customizer we're ready so it sends the current draft (covers the
    // race where the iframe mounts after the customizer's first post).
    window.parent.postMessage({ type: THEME_PREVIEW_READY }, window.location.origin)

    return () => {
      window.removeEventListener('message', onMessage)
    }
  }, [isPreview])

  return { isPreview, preview }
}
