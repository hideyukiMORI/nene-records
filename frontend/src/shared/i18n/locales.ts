/**
 * i18n locale definitions — 6 locales matching NENE2 docs locale set.
 *
 * NENE2 docs locale IDs (in .vitepress/config.mts): en (root), ja, fr, zh, pt-br, de
 * Admin SPA uses BCP 47 IDs; mapping tracked in nene2Id field.
 */

export type SupportedLocale = 'en' | 'ja' | 'fr' | 'zh-Hans' | 'pt-BR' | 'de'

export interface LocaleMeta {
  /** Native language name displayed in locale selector */
  label: string
  /** Text direction */
  dir: 'ltr' | 'rtl'
  /** NENE2 docs locale ID if different from SupportedLocale (null = same) */
  nene2Id: string | null
}

export const LOCALES: Record<SupportedLocale, LocaleMeta> = {
  en: { label: 'English', dir: 'ltr', nene2Id: null },
  ja: { label: '日本語', dir: 'ltr', nene2Id: 'ja' },
  fr: { label: 'Français', dir: 'ltr', nene2Id: 'fr' },
  'zh-Hans': { label: '中文（简体）', dir: 'ltr', nene2Id: 'zh' },
  'pt-BR': { label: 'Português (Brasil)', dir: 'ltr', nene2Id: 'pt-br' },
  de: { label: 'Deutsch', dir: 'ltr', nene2Id: 'de' },
}

export const DEFAULT_LOCALE: SupportedLocale = 'en'

export const SUPPORTED_LOCALE_IDS = Object.keys(LOCALES) as SupportedLocale[]

/**
 * Resolve a raw locale string (from localStorage or navigator.language)
 * to a supported locale with en fallback.
 *
 * Examples: 'ja-JP' → 'ja', 'zh-Hans-CN' → 'zh-Hans', 'unknown' → 'en'
 */
export function resolveLocale(raw: string): SupportedLocale {
  if (SUPPORTED_LOCALE_IDS.includes(raw as SupportedLocale)) {
    return raw as SupportedLocale
  }
  // Try prefix match (e.g., 'ja-JP' → 'ja', 'pt-BR' → 'pt-BR')
  const prefix = raw.split('-').slice(0, 2).join('-')
  if (SUPPORTED_LOCALE_IDS.includes(prefix as SupportedLocale)) {
    return prefix as SupportedLocale
  }
  const singlePrefix = raw.split('-')[0]
  if (SUPPORTED_LOCALE_IDS.includes(singlePrefix as SupportedLocale)) {
    return singlePrefix as SupportedLocale
  }
  return DEFAULT_LOCALE
}
