import { describe, expect, it } from 'vitest'
import { parseBundleDocument, serializeBundleDocument } from './bundle-document'

describe('bundle-document', () => {
  it('parses an envelope and round-trips', () => {
    const json = serializeBundleDocument({ html: '<h1>x</h1>', seoText: '# x' })
    expect(parseBundleDocument(json)).toEqual({ html: '<h1>x</h1>', seoText: '# x' })
  })

  it('treats a legacy raw-HTML value as html with empty seoText', () => {
    expect(parseBundleDocument('<h1>legacy</h1>')).toEqual({ html: '<h1>legacy</h1>', seoText: '' })
  })

  it('returns empty for empty input', () => {
    expect(parseBundleDocument('')).toEqual({ html: '', seoText: '' })
  })
})
