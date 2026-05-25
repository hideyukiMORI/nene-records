import { describe, it, expect } from 'vitest'
import { resolveLocale, DEFAULT_LOCALE } from './locales'

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
