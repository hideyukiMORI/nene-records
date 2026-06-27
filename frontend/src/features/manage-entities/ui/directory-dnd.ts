/**
 * Drag-and-drop payload for moving a directory record under a new parent (#659).
 * `dataTransfer` is opaque during dragover, so the active payload lives in a
 * module ref (matches widget-dnd). Only records — not pure folders — are dragged.
 */
export interface DirectoryDragPayload {
  id: number
  permalink: string
  label: string
}

let current: DirectoryDragPayload | null = null

export function setDirectoryDragPayload(payload: DirectoryDragPayload): void {
  current = payload
}

export function getDirectoryDragPayload(): DirectoryDragPayload | null {
  return current
}

export function clearDirectoryDragPayload(): void {
  current = null
}

/** The last path segment of a permalink (`/company/about` → `about`). */
function lastSegment(permalink: string): string {
  return (
    permalink
      .split('/')
      .filter((segment) => segment !== '')
      .pop() ?? ''
  )
}

/** The permalink a record would get if moved under `targetPath`. */
export function moveTargetPermalink(payload: DirectoryDragPayload, targetPath: string): string {
  return `${targetPath}/${lastSegment(payload.permalink)}`
}

/**
 * A node at `targetPath` can receive `payload` when it is neither the record
 * itself nor one of its descendants (which would orphan / cycle it), and the move
 * actually changes the permalink (not a drop onto its current parent).
 */
export function canDropInto(payload: DirectoryDragPayload, targetPath: string): boolean {
  if (targetPath === payload.permalink) {
    return false
  }
  if (targetPath.startsWith(`${payload.permalink}/`)) {
    return false
  }
  return moveTargetPermalink(payload, targetPath) !== payload.permalink
}

/**
 * The new ordering after moving the item at `index` by `delta` positions (#659).
 * Out-of-range moves return the input unchanged.
 */
export function moveInOrder(ids: number[], index: number, delta: number): number[] {
  const moved = ids[index]
  const to = index + delta
  if (moved === undefined || to < 0 || to >= ids.length) {
    return ids
  }
  const next = ids.filter((_, i) => i !== index)
  next.splice(to, 0, moved)
  return next
}

/** The parent path of a permalink (`/company/about` → `/company`; top-level → ``). */
export function parentPath(permalink: string): string {
  const index = permalink.lastIndexOf('/')
  return index <= 0 ? '' : permalink.slice(0, index)
}

/**
 * Where a drop lands within a row by pointer Y (#675): the top/bottom edge (30%)
 * means "reorder before/after this sibling"; the middle means "move into".
 */
export function dropPosition(
  clientY: number,
  top: number,
  height: number,
): 'before' | 'middle' | 'after' {
  if (height <= 0) {
    return 'middle'
  }
  const ratio = (clientY - top) / height
  if (ratio < 0.3) {
    return 'before'
  }
  if (ratio > 0.7) {
    return 'after'
  }
  return 'middle'
}

/**
 * The new id ordering after moving `movedId` to just before/after `targetId`
 * within a sibling list (#675). Returns the input unchanged if target is absent.
 */
export function reorderByInsert(
  ids: number[],
  movedId: number,
  targetId: number,
  position: 'before' | 'after',
): number[] {
  const without = ids.filter((id) => id !== movedId)
  const targetIndex = without.indexOf(targetId)
  if (targetIndex === -1) {
    return ids
  }
  without.splice(position === 'before' ? targetIndex : targetIndex + 1, 0, movedId)
  return without
}
