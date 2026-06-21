import { act, renderHook } from '@testing-library/react'
import { type RefObject } from 'react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useHeaderShrink } from './use-header-shrink'

let lastCallback: IntersectionObserverCallback | null
let observedEl: Element | null

class MockIntersectionObserver {
  constructor(cb: IntersectionObserverCallback) {
    lastCallback = cb
  }
  observe(el: Element): void {
    observedEl = el
  }
  unobserve(): void {}
  disconnect(): void {
    observedEl = null
  }
}

function fire(isIntersecting: boolean): void {
  act(() => {
    lastCallback?.([{ isIntersecting } as IntersectionObserverEntry], {} as IntersectionObserver)
  })
}

function render(enabled: boolean) {
  const sentinel = document.createElement('div')
  const sentinelRef = { current: sentinel } as RefObject<Element | null>
  return renderHook(() => useHeaderShrink({ enabled, sentinelRef }))
}

beforeEach(() => {
  lastCallback = null
  observedEl = null
  vi.stubGlobal('IntersectionObserver', MockIntersectionObserver)
})

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('useHeaderShrink', () => {
  it('observes the sentinel and stays unshrunk while it is in view', () => {
    const { result } = render(true)
    expect(observedEl).not.toBeNull()

    fire(true)
    expect(result.current).toBe(false)
  })

  it('shrinks once the sentinel scrolls out of view', () => {
    const { result } = render(true)

    fire(false)
    expect(result.current).toBe(true)
  })

  it('does nothing and stays unshrunk when disabled', () => {
    const { result } = render(false)

    expect(observedEl).toBeNull()
    expect(result.current).toBe(false)
  })
})
