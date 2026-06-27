import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import {
  render,
  renderHook,
  type RenderHookOptions,
  type RenderHookResult,
  type RenderOptions,
  type RenderResult,
} from '@testing-library/react'
import type { ReactElement, ReactNode } from 'react'
import { MemoryRouter } from 'react-router-dom'
import { I18nProvider } from '@/shared/i18n'

export function createTestQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  })
}

export function renderWithProviders(ui: ReactElement, options?: RenderOptions): RenderResult {
  const queryClient = createTestQueryClient()

  return render(ui, {
    wrapper: ({ children }) => (
      <I18nProvider>
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
      </I18nProvider>
    ),
    ...options,
  })
}

/**
 * Render a hook wrapped in the app's providers (React Query + i18n).
 * Pairs with MSW handlers to exercise feature hooks at the network boundary.
 */
export function renderHookWithProviders<Result, Props>(
  hook: (initialProps: Props) => Result,
  options?: RenderHookOptions<Props>,
): RenderHookResult<Result, Props> {
  const queryClient = createTestQueryClient()

  return renderHook(hook, {
    // MemoryRouter so hooks using router context (e.g. useSearchParams) work. Hook
    // tests never supply their own Router, so there's no nesting risk (unlike
    // renderWithProviders, where component tests often wrap their own).
    wrapper: ({ children }: { children: ReactNode }) => (
      <MemoryRouter>
        <I18nProvider>
          <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
        </I18nProvider>
      </MemoryRouter>
    ),
    ...options,
  })
}
