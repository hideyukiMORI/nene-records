import { describe, it, expect } from 'vitest'
import { resolveLocale, DEFAULT_LOCALE } from './locales'
import { de } from './messages/de'
import { en, type MessageCatalog } from './messages/en'
import { fr } from './messages/fr'
import { ja } from './messages/ja'
import { ptBR } from './messages/pt-BR'
import { zhHans } from './messages/zh-Hans'

describe('resolveLocale', () => {
  it('returns a supported locale unchanged', () => {
    expect(resolveLocale('ja')).toBe('ja')
    expect(resolveLocale('fr')).toBe('fr')
    expect(resolveLocale('zh-Hans')).toBe('zh-Hans')
    expect(resolveLocale('pt-BR')).toBe('pt-BR')
    expect(resolveLocale('de')).toBe('de')
    expect(resolveLocale('en')).toBe('en')
  })

  it('resolves a language-region tag to the matching supported locale', () => {
    expect(resolveLocale('ja-JP')).toBe('ja')
    expect(resolveLocale('de-AT')).toBe('de')
    expect(resolveLocale('fr-FR')).toBe('fr')
  })

  it('resolves pt-BR (two-segment BCP 47) correctly', () => {
    expect(resolveLocale('pt-BR')).toBe('pt-BR')
    // 'pt' alone has no match → falls back to DEFAULT
    expect(resolveLocale('pt')).toBe(DEFAULT_LOCALE)
  })

  it('falls back to DEFAULT_LOCALE for unknown locales', () => {
    expect(resolveLocale('unknown')).toBe(DEFAULT_LOCALE)
    expect(resolveLocale('xx-YY')).toBe(DEFAULT_LOCALE)
    expect(resolveLocale('')).toBe(DEFAULT_LOCALE)
  })
})

// All non-English catalogs must be complete: the admin UI is fully localized in
// 6 languages, so every key in en must be translated in each locale (no English
// fallback). Adding an en key therefore requires adding it to all of these.
const LOCALE_CATALOGS: ReadonlyArray<{
  id: string
  file: string
  messages: Partial<MessageCatalog>
}> = [
  { id: 'ja', file: 'ja.ts', messages: ja },
  { id: 'de', file: 'de.ts', messages: de },
  { id: 'fr', file: 'fr.ts', messages: fr },
  { id: 'pt-BR', file: 'pt-BR.ts', messages: ptBR },
  { id: 'zh-Hans', file: 'zh-Hans.ts', messages: zhHans },
]

function placeholderTokens(value: string): string {
  return [...value.matchAll(/\{\{(\w+)\}\}/g)]
    .map((m) => m[1] ?? '')
    .sort()
    .join(',')
}

describe('i18n key coverage', () => {
  const enKeys = Object.keys(en) as Array<keyof MessageCatalog>

  it.each(LOCALE_CATALOGS)('$id translates every key defined in en', ({ file, messages }) => {
    const missing = enKeys.filter((key) => !(key in messages))

    if (missing.length > 0) {
      throw new Error(
        `${file} is missing ${String(missing.length)} key(s):\n` +
          missing.map((k) => `  • ${k}`).join('\n') +
          `\n\nAdd the missing keys to frontend/src/shared/i18n/messages/${file}`,
      )
    }

    expect(missing).toHaveLength(0)
  })

  it.each(LOCALE_CATALOGS)(
    '$id preserves every {{placeholder}} token from en',
    ({ file, messages }) => {
      const mismatches: string[] = []
      for (const key of enKeys) {
        const translated = messages[key]
        if (translated === undefined) continue
        const expected = placeholderTokens(en[key])
        const actual = placeholderTokens(translated)
        if (expected !== actual) {
          mismatches.push(`  • ${key}: en{${expected}} vs ${file}{${actual}}`)
        }
      }

      if (mismatches.length > 0) {
        throw new Error(
          `${file} has ${String(mismatches.length)} placeholder mismatch(es):\n` +
            mismatches.join('\n'),
        )
      }

      expect(mismatches).toHaveLength(0)
    },
  )
})
