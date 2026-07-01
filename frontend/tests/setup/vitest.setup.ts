import '@testing-library/jest-dom/vitest'

// jsdom does not implement ResizeObserver. Components that observe element size
// (e.g. the public header's nav fit-probe) construct one on mount; provide an
// inert no-op so they render under test without throwing. Tests that need to
// drive resize callbacks can stub this global.
if (typeof globalThis.ResizeObserver === 'undefined') {
  globalThis.ResizeObserver = class {
    observe(): void {}
    unobserve(): void {}
    disconnect(): void {}
  }
}

// jsdom does not implement matchMedia. Provide an inert default (no match, no-op
// listeners) so components that read viewport media queries via `useMediaQuery`
// render their desktop branch under test. Tests that assert mobile behavior
// override this with `vi.stubGlobal('matchMedia', …)`.
if (typeof window !== 'undefined' && typeof window.matchMedia !== 'function') {
  window.matchMedia = (query: string): MediaQueryList =>
    ({
      matches: false,
      media: query,
      onchange: null,
      addEventListener: () => {},
      removeEventListener: () => {},
      addListener: () => {},
      removeListener: () => {},
      dispatchEvent: () => false,
    }) as MediaQueryList
}
