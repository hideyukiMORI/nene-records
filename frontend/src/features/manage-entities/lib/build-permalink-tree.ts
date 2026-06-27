import type { EntityStatus } from '@/entities/entity'

export interface DirectoryRecord {
  id: number
  permalink: string
  label: string
  status: EntityStatus
  updatedAt: string | null
}

export interface DirectoryNode {
  /** The single path segment this node represents (e.g. `about`). */
  segment: string
  /** The cumulative path to this node (e.g. `/company/about`). */
  path: string
  /** The record whose permalink is exactly this path, or null for a pure folder. */
  record: DirectoryRecord | null
  children: DirectoryNode[]
}

function sortNodes(nodes: DirectoryNode[]): DirectoryNode[] {
  nodes.sort((a, b) => a.segment.localeCompare(b.segment))
  for (const node of nodes) {
    sortNodes(node.children)
  }
  return nodes
}

/**
 * Builds a directory tree from records' permalink paths (#651 PR3). A record at
 * `/a/b/c` creates folders `a` → `b` and a leaf `c` carrying the record. A node
 * can be both a record and a folder — a section page that also has children.
 * Records without a path segment are skipped.
 */
export function buildPermalinkTree(records: DirectoryRecord[]): DirectoryNode[] {
  const roots: DirectoryNode[] = []

  for (const record of records) {
    const segments = record.permalink.split('/').filter((segment) => segment !== '')
    if (segments.length === 0) {
      continue
    }

    let siblings = roots
    let cumulative = ''
    segments.forEach((segment, index) => {
      cumulative += `/${segment}`
      let node = siblings.find((candidate) => candidate.segment === segment)
      if (node === undefined) {
        node = { segment, path: cumulative, record: null, children: [] }
        siblings.push(node)
      }
      if (index === segments.length - 1) {
        node.record = record
      }
      siblings = node.children
    })
  }

  return sortNodes(roots)
}

/**
 * Filters a built tree to the branches matching a query (#659): a node is kept
 * when its path or record label contains the query (case-insensitive), or any
 * descendant is kept — so a match stays reachable through its ancestors. An empty
 * query returns the tree unchanged.
 */
export function filterDirectoryTree(nodes: DirectoryNode[], query: string): DirectoryNode[] {
  const needle = query.trim().toLowerCase()
  if (needle === '') {
    return nodes
  }

  const keep = (node: DirectoryNode): DirectoryNode | null => {
    const selfMatches =
      node.path.toLowerCase().includes(needle) ||
      (node.record?.label.toLowerCase().includes(needle) ?? false)
    const children = node.children
      .map(keep)
      .filter((child): child is DirectoryNode => child !== null)

    if (selfMatches || children.length > 0) {
      return { ...node, children }
    }
    return null
  }

  return nodes.map(keep).filter((node): node is DirectoryNode => node !== null)
}
