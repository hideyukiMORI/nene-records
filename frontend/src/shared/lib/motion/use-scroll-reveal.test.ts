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

function intersect(...targets: Element[]): void {
  const entries = targets.map(
    (target) => ({ isIntersecting: true, target }) as IntersectionObserverEntry,
  )
  act(() => {
    lastCallback?.(entries, fakeObserver)
  })
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
})

afterEach(() => {
  vi.unstubAllGlobals()
  document.body.innerHTML = ''
})

describe('useScrollReveal', () => {
  it('tags and observes only matching elements when enabled', () => {
    const root = mountContainer(
      '<div class="card">a</div><div class="card">b</div><div class="x">c</div>',
    )
    renderReveal(root, true)

    root.querySelectorAll('.card').forEach((card) => {
      expect(card.hasAttribute('data-motion-reveal-item')).toBe(true)
      expect(card.hasAttribute('data-revealed')).toBe(false) // not until it intersects
    })
    expect(observed).toHaveLength(2)
    expect(root.querySelector('.x')?.hasAttribute('data-motion-reveal-item')).toBe(false)
  })

  it('reveals, unobserves and staggers items as they intersect', () => {
    const root = mountContainer('<div class="card">a</div><div class="card">b</div>')
    renderReveal(root, true)
    const [first, second] = root.querySelectorAll<HTMLElement>('.card')
    expect(observed).toHaveLength(2)

    intersect(first, second)

    expect(first.hasAttribute('data-revealed')).toBe(true)
    expect(second.hasAttribute('data-revealed')).toBe(true)
    // Cascade index applied for the second item in the batch.
    expect(first.style.getPropertyValue('--motion-reveal-index')).toBe('0')
    expect(second.style.getPropertyValue('--motion-reveal-index')).toBe('1')
    expect(observed).toHaveLength(0)
  })

  it('does nothing when disabled', () => {
    const root = mountContainer('<div class="card">a</div>')
    renderReveal(root, false)

    expect(observed).toHaveLength(0)
    expect(root.querySelector('.card')?.hasAttribute('data-motion-reveal-item')).toBe(false)
  })

  it('re-observes an already-tagged but unrevealed item when the effect re-runs', () => {
    // Guards the StrictMode / back-navigation regression: a tagged-but-unrevealed
    // item must be re-observed (not skipped) when the effect re-runs.
    const root = mountContainer('<div class="card">a</div>')
    const ref = { current: root } as RefObject<HTMLElement | null>

    const { rerender } = renderHook(
      ({ key }: { key: string }) => {
        useScrollReveal({ containerRef: ref, enabled: true, selector: '.card', scanKey: key })
      },
      { initialProps: { key: '/' } },
    )
    expect(observed).toHaveLength(1)

    // Effect teardown (mock disconnect empties `observed`) + re-run.
    rerender({ key: '/next' })

    expect(root.querySelector('.card')?.hasAttribute('data-revealed')).toBe(false)
    expect(observed).toHaveLength(1) // re-observed, not skipped
  })
})
