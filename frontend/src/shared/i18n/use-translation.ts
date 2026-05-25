import { useContext } from 'react'
import { I18nContext, type I18nContextValue } from './i18n-context-ref'

/**
 * Primary i18n hook for Admin UI components.
 *
 * @example
 * const { t, locale, setLocale } = useTranslation()
 * return <h1>{t('admin.home.title')}</h1>
 *
 * @example with params
 * t('admin.entityTypes.delete.description', { name: entityType.name })
 */
export function useTranslation(): I18nContextValue {
  const ctx = useContext(I18nContext)
  if (ctx === null) {
    throw new Error('useTranslation must be called inside <I18nProvider>')
  }
  return ctx
}
