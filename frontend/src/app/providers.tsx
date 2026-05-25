import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useState, type ReactNode } from 'react'
import { seedPublicRecordViewCache } from '@/shared/lib/seed-public-record-view-cache'
import { AppError } from '@/shared/api/client'
import { I18nProvider } from '@/shared/i18n'
import { AuthGate } from './auth-gate'
import { RootErrorBoundary } from './root-error-boundary'

function createAppQueryClient(): QueryClient {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 30_000,
        retry: (failureCount, error) =>
          failureCount < 2 && error instanceof AppError && error.isRetryable,
        refetchOnWindowFocus: import.meta.env.PROD,
      },
      mutations: {
        retry: false,
      },
    },
  })

  seedPublicRecordViewCache(queryClient)

  return queryClient
}

export function AppProviders({ children }: { children: ReactNode }) {
  const [queryClient] = useState(createAppQueryClient)

  return (
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        <RootErrorBoundary>
          <AuthGate>{children}</AuthGate>
        </RootErrorBoundary>
      </QueryClientProvider>
    </I18nProvider>
  )
}
