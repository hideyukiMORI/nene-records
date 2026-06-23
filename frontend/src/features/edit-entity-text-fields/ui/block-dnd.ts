/**
 * Drag payload for the block board. dataTransfer is opaque during dragover, so
 * the active payload is kept in a module ref (mirrors manage-widgets/widget-dnd).
 *
 * The board only reorders existing blocks (palette uses click-to-add), so the
 * payload carries just the dragged block id. Re-add a `'new'` variant here if a
 * drag-from-palette flow is introduced (see widget-dnd for the shape).
 */
export type BlockDragPayload = { kind: 'move'; id: string }

let current: BlockDragPayload | null = null

export function setBlockDragPayload(payload: BlockDragPayload): void {
  current = payload
}

export function getBlockDragPayload(): BlockDragPayload | null {
  return current
}

export function clearBlockDragPayload(): void {
  current = null
}

/** Index where a dropped block should land, based on pointer vs card midpoints. */
export function computeBlockDropIndex(container: HTMLElement, clientY: number): number {
  const cards = [...container.querySelectorAll('[data-bcard]')]
  for (let i = 0; i < cards.length; i++) {
    const card = cards[i]
    if (card === undefined) {
      continue
    }
    const rect = card.getBoundingClientRect()
    if (clientY < rect.top + rect.height / 2) {
      return i
    }
  }
  return cards.length
}
