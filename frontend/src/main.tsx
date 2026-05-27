import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { AppProviders } from '@/app/providers'
import { AppRouter } from '@/app/router'
import { applyLocaleFontFamily, resolveLocale } from '@/shared/i18n'
import '@/shared/ui/theme/index.css'
import '@/fonts'

// FOUC 防止: React レンダリング前にロケール検出してフォントを即適用する。
// (I18nProvider 内でも同じ処理をするが、こちらが先に動く)
const storedLocale = (() => {
  try {
    return localStorage.getItem('nene-locale') ?? navigator.language
  } catch {
    return navigator.language
  }
})()
applyLocaleFontFamily(resolveLocale(storedLocale))

async function enableMocking(): Promise<void> {
  if (import.meta.env.VITE_MOCK_API !== 'true') return
  const { worker } = await import('./mocks/browser')
  await worker.start({ onUnhandledRequest: 'bypass' })
}

const rootElement = document.getElementById('root')
if (rootElement === null) {
  throw new Error('Root element #root not found')
}

void enableMocking().then(() => {
  createRoot(rootElement).render(
    <StrictMode>
      <AppProviders>
        <AppRouter />
      </AppProviders>
    </StrictMode>,
  )
})
