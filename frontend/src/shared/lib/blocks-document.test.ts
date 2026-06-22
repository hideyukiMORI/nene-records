import { describe, expect, it } from 'vitest'
import {
  createBlock,
  isSafeHref,
  parseBlocksDocument,
  serializeBlocksDocument,
  validateBlock,
  type Block,
} from './blocks-document'

describe('blocks-document', () => {
  it('parses a valid document', () => {
    const doc = JSON.stringify([
      { id: 'b1', type: 'text', data: { markdown: 'hello' } },
      { id: 'b2', type: 'callout', data: { kind: 'warn', body: 'careful', title: 'Note' } },
    ])

    const blocks = parseBlocksDocument(doc)

    expect(blocks).toHaveLength(2)
    expect(blocks[0]).toEqual({ id: 'b1', type: 'text', data: { markdown: 'hello' } })
    expect(blocks[1]).toEqual({
      id: 'b2',
      type: 'callout',
      data: { kind: 'warn', body: 'careful', title: 'Note' },
    })
  })

  it('returns [] for empty and invalid input', () => {
    expect(parseBlocksDocument('')).toEqual([])
    expect(parseBlocksDocument('not json')).toEqual([])
    expect(parseBlocksDocument('{"not":"a list"}')).toEqual([])
  })

  it('drops unknown block types and coerces malformed data', () => {
    const doc = JSON.stringify([
      { id: 'b1', type: 'mystery', data: {} },
      { id: 'b2', type: 'callout', data: { kind: 'bogus' } },
    ])

    const blocks = parseBlocksDocument(doc)

    expect(blocks).toHaveLength(1)
    expect(blocks[0]?.type).toBe('callout')
    expect(blocks[0]).toEqual({ id: 'b2', type: 'callout', data: { kind: 'info', body: '' } })
  })

  it('serializes and drops empty callout titles', () => {
    const blocks: Block[] = [
      { id: 'b1', type: 'callout', data: { kind: 'ok', body: 'done', title: '   ' } },
    ]

    const json = serializeBlocksDocument(blocks)

    expect(JSON.parse(json)).toEqual([
      { id: 'b1', type: 'callout', data: { kind: 'ok', body: 'done' } },
    ])
  })

  it('creates blocks with defaults and a stable id', () => {
    const text = createBlock('text')
    expect(text.type).toBe('text')
    expect(text.id).not.toBe('')

    const callout = createBlock('callout')
    expect(callout.type).toBe('callout')
    if (callout.type === 'callout') {
      expect(callout.data.kind).toBe('info')
    }
  })

  it('validates required content', () => {
    expect(validateBlock({ id: 'a', type: 'text', data: { markdown: '' } })).toBe(
      'markdown-required',
    )
    expect(validateBlock({ id: 'a', type: 'text', data: { markdown: 'x' } })).toBeNull()
    expect(validateBlock({ id: 'a', type: 'callout', data: { kind: 'info', body: '' } })).toBe(
      'body-required',
    )
    expect(
      validateBlock({ id: 'a', type: 'callout', data: { kind: 'info', body: 'x' } }),
    ).toBeNull()
  })

  it('parses, validates, and serializes hero blocks', () => {
    const doc = JSON.stringify([
      {
        id: 'h1',
        type: 'hero',
        data: { variant: 'minimal', heading: 'Title', ctaLabel: 'Go', ctaUrl: '/x', lead: '  ' },
      },
    ])

    const blocks = parseBlocksDocument(doc)
    expect(blocks[0]).toMatchObject({
      type: 'hero',
      data: { variant: 'minimal', heading: 'Title' },
    })

    // heading required
    expect(
      validateBlock({ id: 'h', type: 'hero', data: { variant: 'standard', heading: '' } }),
    ).toBe('heading-required')
    expect(validateBlock(blocks[0] as Block)).toBeNull()

    // empty optionals (lead) dropped on serialize
    const json = serializeBlocksDocument(blocks)
    const data = (JSON.parse(json) as { data: Record<string, unknown> }[])[0]?.data
    expect(data).not.toHaveProperty('lead')
    expect(data).toMatchObject({ ctaLabel: 'Go', ctaUrl: '/x' })

    // unknown hero variant coerces to standard
    const coerced = parseBlocksDocument('[{"id":"h","type":"hero","data":{"heading":"x"}}]')
    expect(coerced[0]).toMatchObject({ data: { variant: 'standard' } })
  })

  it('allowlists safe hrefs', () => {
    expect(isSafeHref('/path')).toBe(true)
    expect(isSafeHref('https://example.com')).toBe(true)
    expect(isSafeHref('#anchor')).toBe(true)
    expect(isSafeHref('mailto:a@b.c')).toBe(true)
    expect(isSafeHref('javascript:alert(1)')).toBe(false)
    expect(isSafeHref('data:text/html,x')).toBe(false)
    expect(isSafeHref('')).toBe(false)
  })

  it('creates a hero block with defaults', () => {
    const hero = createBlock('hero')
    expect(hero.type).toBe('hero')
    if (hero.type === 'hero') {
      expect(hero.data.variant).toBe('standard')
    }
  })
})
