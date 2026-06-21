import { act, renderHook } from '@testing-library/react'
import { type RefObject } from 'react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useScrollReveal } from './use-scroll-reveal'

let observed: Element[]
let lastCallback: IntersectionObserverCallback | null

// Synthetic observer passed to the callback so the hook's `obs.unobserve(...)`
// mutates the same `observed` list the mock tracks.
const fakeObserver = {
  unobserve(el: Element): void {
    observed = observed.filter((entry) => entry !== el)
  },
} as unknown as IntersectionObserver

class MockIntersectionObserver {
  constructor(cb: IntersectionObserverCallback) {
    lastCallback = cb
  }
  observe(el: Element): void {
    observed.push(el)
  }
  unobserve(el: Element): void {
    observed = observed.filter((entry) => entry !== el)
  }
  disconnect(): void {
    observed = []
  }
}

/** Force an element's vertical position relative to the (768px tall) jsdom viewport. */
function setTop(el: Element, top: number): void {
  el.getBoundingClientRect = () =>
    ({
      top,
      bottom: top + 100,
      left: 0,
      right: 0,
      width: 100,
      height: 100,
      x: 0,
      y: top,
    }) as DOMRect
}

function mountContainer(html: string): HTMLDivElement {
  const root = document.createElement('div')
  root.innerHTML = html
  document.body.appendChild(root)
  return root
}

function renderReveal(root: HTMLElement, enabled: boolean): void {
  const ref = { current: root } as RefObject<HTMLElement | null>
  renderHook(() => {
    useScrollReveal({ containerRef: ref, enabled, selector: '.card', scanKey: '/' })
  })
}

beforeEach(() => {
  observed = []
  lastCallback = null
  vi.stubGlobal('IntersectionObserver', MockIntersectionObserver)
  // Run rAF synchronously so the in-view reveal is observable in the test.
  vi.stubGlobal('requestAnimationFrame', (cb: FrameRequestCallback) => {
    cb(0)
    return 1
  })
  vi.stubGlobal('cancelAnimationFrame', () => {})
})

afterEach(() => {
  vi.unstubAllGlobals()
  document.body.innerHTML = ''
})

describe('useScrollReveal', () => {
  it('tags matching elements and reveals the ones already in view', () => {
    const root = mountContainer(
      '<div class="card">a</div><div class="card">b</div><div class="x">c</div>',
    )
    root.querySelectorAll('.card').forEach((card) => {
      setTop(card, 100)
    }) // in view (< 768)
    renderReveal(root, true)

    root.querySelectorAll('.card').forEach((card) => {
      expect(card.hasAttribute('data-motion-reveal-item')).toBe(true)
      expect(card.hasAttribute('data-revealed')).toBe(true)
    })
    // In-view items are revealed directly, not handed to the IntersectionObserver.
    expect(observed).toHaveLength(0)
    expect(root.querySelector('.x')?.hasAttribute('data-motion-reveal-item')).toBe(false)
  })

  it('defers below-the-fold elements to the IntersectionObserver and reveals on intersect', () => {
    const root = mountContainer('<div class="card">a</div>')
    const card = root.querySelector('.card')
    expect(card).not.toBeNull()
    if (card !== null) {
      setTop(card, 5000)
    } // below the fold (> 768)
    renderReveal(root, true)

    expect(card?.hasAttribute('data-motion-reveal-item')).toBe(true)
    expect(card?.hasAttribute('data-revealed')).toBe(false)
    expect(observed).toHaveLength(1)

    act(() => {
      lastCallback?.(
        [{ isIntersecting: true, target: card } as IntersectionObserverEntry],
        fakeObserver,
      )
    })

    expect(card?.hasAttribute('data-revealed')).toBe(true)
    expect(observed).toHaveLength(0)
  })

  it('does nothing when disabled', () => {
    const root = mountContainer('<div class="card">a</div>')
    renderReveal(root, false)

    expect(observed).toHaveLength(0)
    expect(root.querySelector('.card')?.hasAttribute('data-motion-reveal-item')).toBe(false)
  })
})
