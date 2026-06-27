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
