import { describe, expect, it } from 'vitest'
import { createSlugger, extractHeadings } from './markdown-headings'

describe('createSlugger', () => {
  it('slugifies and de-duplicates repeated text', () => {
    const slug = createSlugger()
    expect(slug('Getting Started')).toBe('getting-started')
    expect(slug('Getting Started')).toBe('getting-started-1')
    expect(slug('Getting Started')).toBe('getting-started-2')
  })

  it('falls back to "section" for symbol-only text', () => {
    const slug = createSlugger()
    expect(slug('***')).toBe('section')
  })

  it('keeps unicode letters', () => {
    const slug = createSlugger()
    expect(slug('概要 Overview')).toBe('概要-overview')
  })
})

describe('extractHeadings', () => {
  it('returns headings in document order with depth and slug', () => {
    const md = ['# Title', '', '## Intro', '', '### Details', '', 'body'].join('\n')
    expect(extractHeadings(md)).toEqual([
      { depth: 1, text: 'Title', slug: 'title' },
      { depth: 2, text: 'Intro', slug: 'intro' },
      { depth: 3, text: 'Details', slug: 'details' },
    ])
  })

  it('strips inline markdown from heading text', () => {
    expect(extractHeadings('## **Bold** and [link](/x)')).toEqual([
      { depth: 2, text: 'Bold and link', slug: 'bold-and-link' },
    ])
  })

  it('ignores # characters inside fenced code blocks', () => {
    const md = ['## Real', '', '```', '# not a heading', '```', '', '## Also Real'].join('\n')
    expect(extractHeadings(md).map((h) => h.text)).toEqual(['Real', 'Also Real'])
  })

  it('de-duplicates identical headings across levels in order', () => {
    const md = ['## Notes', '### Notes'].join('\n')
    expect(extractHeadings(md).map((h) => h.slug)).toEqual(['notes', 'notes-1'])
  })

  it('ignores non-heading lines and trailing ATX hashes', () => {
    expect(extractHeadings('## Heading ##\nnot # a heading')).toEqual([
      { depth: 2, text: 'Heading', slug: 'heading' },
    ])
  })
})
