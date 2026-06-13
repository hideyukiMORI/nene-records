import type { WidgetType } from '@/entities/widget'

/**
 * Drag payload shared across components. dataTransfer is opaque during dragover,
 * so the active payload is kept in a module ref (matches the prototype).
 */
export type DragPayload = { kind: 'new'; type: WidgetType } | { kind: 'move'; id: number }

let current: DragPayload | null = null

export function setDragPayload(p: DragPayload): void {
  current = p
}

export function getDragPayload(): DragPayload | null {
  return current
}

export function clearDragPayload(): void {
  current = null
}

/** Index where a dropped item should land, based on pointer vs card midpoints. */
export function computeDropIndex(
  container: HTMLElement,
  clientX: number,
  clientY: number,
  flow: 'row' | 'col',
): number {
  const cards = [...container.querySelectorAll('[data-wcard]')]
  const pos = flow === 'row' ? clientX : clientY
  for (let i = 0; i < cards.length; i++) {
    const r = cards[i].getBoundingClientRect()
    const mid = flow === 'row' ? r.left + r.width / 2 : r.top + r.height / 2
    if (pos < mid) return i
  }
  return cards.length
}
