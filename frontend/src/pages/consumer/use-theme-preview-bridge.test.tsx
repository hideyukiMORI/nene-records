import { act, renderHook } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { THEME_PREVIEW_APPLY, THEME_PREVIEW_READY } from '@/shared/lib/theme-preview-protocol'
import { useThemePreviewBridge } from './use-theme-preview-bridge'

const realParent = window.parent

function enterPreviewIframe(): { postMessage: ReturnType<typeof vi.fn> } {
  const parentSpy = { postMessage: vi.fn() }
  Object.defineProperty(window, 'parent', { value: parentSpy, configurable: true })
  window.history.replaceState({}, '', '/search?nene-theme-preview=1')
  return parentSpy
}

afterEach(() => {
  Object.defineProperty(window, 'parent', { value: realParent, configurable: true })
  window.history.replaceState({}, '', '/')
  vi.restoreAllMocks()
})

function applyMessage(overrideCss: string, flagAttrs: Record<string, string>): void {
  window.dispatchEvent(
    new MessageEvent('message', {
      origin: window.location.origin,
      data: { type: THEME_PREVIEW_APPLY, overrideCss, flagAttrs },
    }),
  )
}

describe('useThemePreviewBridge', () => {
  it('is inactive outside a preview iframe', () => {
    const { result } = renderHook(() => useThemePreviewBridge())
    expect(result.current.isPreview).toBe(false)
    expect(result.current.preview).toBeNull()
  })

  describe('inside a preview iframe', () => {
    let parentSpy: { postMessage: ReturnType<typeof vi.fn> }

    beforeEach(() => {
      parentSpy = enterPreviewIframe()
    })

    it('announces readiness to the parent on mount', () => {
      renderHook(() => useThemePreviewBridge())
      expect(parentSpy.postMessage).toHaveBeenCalledWith(
        { type: THEME_PREVIEW_READY },
        window.location.origin,
      )
    })

    it('applies an apply-message from the same origin', () => {
      const { result } = renderHook(() => useThemePreviewBridge())
      expect(result.current.isPreview).toBe(true)

      act(() => {
        applyMessage('.nene-public{--color-accent:red}', { 'data-feed-layout': 'grid' })
      })

      expect(result.current.preview).toEqual({
        overrideCss: '.nene-public{--color-accent:red}',
        flagAttrs: { 'data-feed-layout': 'grid' },
      })
    })

    it('ignores messages from a foreign origin', () => {
      const { result } = renderHook(() => useThemePreviewBridge())

      act(() => {
        window.dispatchEvent(
          new MessageEvent('message', {
            origin: 'https://evil.example',
            data: { type: THEME_PREVIEW_APPLY, overrideCss: 'x', flagAttrs: {} },
          }),
        )
      })

      expect(result.current.preview).toBeNull()
    })
  })
})
