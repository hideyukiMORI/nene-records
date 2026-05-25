import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, type RenderOptions, type RenderResult } from '@testing-library/react'
import type { ReactElement } from 'react'
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
