import { describe, expect, it } from 'vitest'
import {
  createBlock,
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
})
