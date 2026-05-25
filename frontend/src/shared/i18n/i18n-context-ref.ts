import { createContext } from 'react'
import type { SupportedLocale } from './locales'
import type { MessageKey, MessageParams } from './translate'

/**
 * Shared i18n context value type and context object.
 * Lives in a .ts file (no JSX) so the .tsx provider only exports a component
 * (required for Vite fast-refresh compatibility).
 */
export interface I18nContextValue {
  locale: SupportedLocale
  setLocale: (locale: SupportedLocale) => void
  t: (key: MessageKey, params?: MessageParams) => string
}

export const I18nContext = createContext<I18nContextValue | null>(null)
