import { describe, it, expect } from 'vitest'
import { en } from './messages/en'
import { ja } from './messages/ja'
import { translate } from './translate'

describe('translate', () => {
  describe('key lookup', () => {
    it('returns the message for an existing key in the given catalog', () => {
      expect(translate(en, 'admin.nav.home')).toBe('Home')
    })

    it('falls back to English when the key is missing from the locale catalog', () => {
      // ja has nav keys but let's test with a subset catalog that omits them
      const partial = { 'admin.nav.home': '日本語ホーム' }
      expect(translate(partial, 'admin.nav.logout')).toBe(en['admin.nav.logout'])
    })

    it('returns the Japanese string when the key exists in ja', () => {
      expect(translate(ja, 'admin.nav.home')).toBe('ホーム')
    })

    it('falls back to English for keys absent from ja', () => {
      // Temporarily verify a key that is NOT in ja
      const jaWithoutKey: typeof ja = { ...ja }
      delete jaWithoutKey['admin.nav.home']
      expect(translate(jaWithoutKey, 'admin.nav.home')).toBe('Home')
    })
  })

  describe('parameter interpolation', () => {
    it('replaces {{param}} placeholders', () => {
      const result = translate(en, 'admin.entityTypes.delete.description', { name: 'Articles' })
      expect(result).toBe('"Articles" will be removed. This cannot be undone.')
    })

    it('replaces multiple distinct params', () => {
      // Use a synthetic catalog for this test
      const catalog = { ...en, 'common.error.unknown': '{{a}} and {{b}}' }
      expect(translate(catalog, 'common.error.unknown', { a: 'foo', b: 'bar' })).toBe('foo and bar')
    })

    it('leaves unmatched placeholders as-is', () => {
      const catalog = { ...en, 'common.error.unknown': 'Value: {{missing}}' }
      expect(translate(catalog, 'common.error.unknown', {})).toBe('Value: {{missing}}')
    })

    it('returns the raw string when no params are passed', () => {
      expect(translate(en, 'admin.nav.home')).toBe('Home')
    })
  })
})
