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
    })
    expect(observed).toHaveLength(2)
    expect(root.querySelector('.x')?.hasAttribute('data-motion-reveal-item')).toBe(false)
  })

  it('reveals and unobserves an element once it intersects', () => {
    const root = mountContainer('<div class="card">a</div>')
    renderReveal(root, true)
    const card = root.querySelector('.card')
    expect(card).not.toBeNull()

    act(() => {
      lastCallback?.(
        [{ isIntersecting: true, target: card } as IntersectionObserverEntry],
        fakeObserver,
      )
    })

    expect(card?.hasAttribute('data-revealed')).toBe(true)
    // The only observed element was unobserved on reveal.
    expect(observed).toHaveLength(0)
  })

  it('does nothing when disabled', () => {
    const root = mountContainer('<div class="card">a</div>')
    renderReveal(root, false)

    expect(observed).toHaveLength(0)
    expect(root.querySelector('.card')?.hasAttribute('data-motion-reveal-item')).toBe(false)
  })
})
