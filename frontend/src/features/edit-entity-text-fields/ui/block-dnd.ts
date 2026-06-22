import type { BlockType } from '@/shared/lib/blocks-document'

/**
 * Drag payload for the block board. dataTransfer is opaque during dragover, so
 * the active payload is kept in a module ref (mirrors manage-widgets/widget-dnd).
 */
export type BlockDragPayload = { kind: 'new'; type: BlockType } | { kind: 'move'; id: string }

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
    const rect = cards[i].getBoundingClientRect()
    if (clientY < rect.top + rect.height / 2) {
      return i
    }
  }
  return cards.length
}
