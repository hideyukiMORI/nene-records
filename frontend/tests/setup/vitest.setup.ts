import '@testing-library/jest-dom/vitest'

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
