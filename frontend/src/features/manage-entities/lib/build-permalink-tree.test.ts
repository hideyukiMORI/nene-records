import { describe, expect, it } from 'vitest'
import {
  buildPermalinkTree,
  type DirectoryRecord,
  filterDirectoryTree,
} from './build-permalink-tree'

function record(id: number, permalink: string, label = `Record ${String(id)}`): DirectoryRecord {
  return { id, permalink, label, status: 'published', updatedAt: null, menuOrder: 0 }
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

describe('filterDirectoryTree (#659)', () => {
  const tree = buildPermalinkTree([
    record(1, '/company/about', 'About Us'),
    record(2, '/company/about/team', 'Our Team'),
    record(3, '/legal/privacy', 'Privacy Policy'),
  ])

  it('returns the whole tree for an empty query', () => {
    expect(filterDirectoryTree(tree, '')).toBe(tree)
  })

  it('keeps only branches whose path matches, preserving ancestors', () => {
    const filtered = filterDirectoryTree(tree, 'legal')
    expect(filtered.map((node) => node.segment)).toEqual(['legal'])
    expect(filtered[0]?.children[0]?.segment).toBe('privacy')
  })

  it('matches by record label and keeps the ancestor folders', () => {
    const filtered = filterDirectoryTree(tree, 'our team')
    expect(filtered.map((node) => node.segment)).toEqual(['company'])
    const about = filtered[0]?.children[0]
    expect(about?.segment).toBe('about')
    expect(about?.children[0]?.record?.label).toBe('Our Team')
  })

  it('returns an empty tree when nothing matches', () => {
    expect(filterDirectoryTree(tree, 'nonexistent')).toEqual([])
  })
})
