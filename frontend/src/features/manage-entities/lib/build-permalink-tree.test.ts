import { describe, expect, it } from 'vitest'
import { buildPermalinkTree, type DirectoryRecord } from './build-permalink-tree'

function record(id: number, permalink: string): DirectoryRecord {
  return { id, permalink, label: `Record ${String(id)}`, status: 'published', updatedAt: null }
}

describe('buildPermalinkTree', () => {
  it('returns an empty tree for no records', () => {
    expect(buildPermalinkTree([])).toEqual([])
  })

  it('nests records into folders by path segment', () => {
    const tree = buildPermalinkTree([record(2, '/company/about/team'), record(1, '/company/about')])

    expect(tree).toHaveLength(1)
    const company = tree[0]
    expect(company?.segment).toBe('company')
    expect(company?.path).toBe('/company')
    expect(company?.record).toBeNull() // no page exists at /company → pure folder

    const about = company?.children[0]
    expect(about?.segment).toBe('about')
    expect(about?.record?.id).toBe(1) // /company/about is both a page and a folder
    expect(about?.children[0]?.record?.id).toBe(2)
  })

  it('sorts siblings alphabetically by segment', () => {
    const tree = buildPermalinkTree([
      record(1, '/docs/zebra'),
      record(2, '/docs/alpha'),
      record(3, '/docs/mango'),
    ])

    const docs = tree[0]
    expect(docs?.children.map((node) => node.segment)).toEqual(['alpha', 'mango', 'zebra'])
  })

  it('keeps independent top-level sections separate', () => {
    const tree = buildPermalinkTree([record(1, '/company/about'), record(2, '/legal/privacy')])

    expect(tree.map((node) => node.segment)).toEqual(['company', 'legal'])
  })
})
