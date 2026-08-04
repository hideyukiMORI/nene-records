import '@testing-library/jest-dom/vitest'
import { configure } from '@testing-library/react'

// `findBy*` / `waitFor` の待ち時間（asyncUtilTimeout）は testing-library の既定が 1000ms で、
// vitest の testTimeout（既定 5000ms・本リポは vitest.config.ts で 15000ms）とは**別の予算**。
// 既定のままだと、テストに残り時間があるのに非同期ヘルパだけが先に諦める。
// 実際 #1035 では高負荷時（environment 175s）に初回レンダリングが 1 秒以内に落ち着かず、
// 予算を 4 秒残したまま `findByRole` が失敗して Stop hook ゲートを 3 回誤発火させた。
// 通常時のこのスイートは 1 テスト 200〜500ms 程度なので 5000ms は 10 倍前後の余裕。
// testTimeout はこれより十分上に置く（そうしないと findBy の読みやすい失敗メッセージが出る前に
// テスト全体がタイムアウトする）。
configure({ asyncUtilTimeout: 5000 })

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
