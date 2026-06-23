/**
 * Pure array reordering helpers shared by the block editor's reorderable lists
 * (board, gallery slides, chart series, group/column children) and drag-and-drop.
 * Each returns a new array (immutable) or `null` when the move is a no-op /
 * out of range, so callers `if (next === null) return`.
 */

/** Move the item at `index` one step in `direction` (-1 up / +1 down). */
export function moveItem<T>(items: readonly T[], index: number, direction: -1 | 1): T[] | null {
  const target = index + direction
  // Guard both source and target so an out-of-range `index` (e.g. from a failed
  // `indexOf`) can't splice the wrong (negative-index) element.
  if (index < 0 || index >= items.length || target < 0 || target >= items.length) {
    return null
  }
  const next = items.slice()
  const [moved] = next.splice(index, 1)
  if (moved === undefined) {
    return null
  }
  next.splice(target, 0, moved)
  return next
}

/**
 * Move the item at `fromIndex` to land at `toIndex`, where `toIndex` is measured
 * against the original array (the drop-line index). Returns `null` when
 * `fromIndex` is out of range.
 */
export function reorderItem<T>(
  items: readonly T[],
  fromIndex: number,
  toIndex: number,
): T[] | null {
  if (fromIndex < 0 || fromIndex >= items.length) {
    return null
  }
  const next = items.slice()
  const [moved] = next.splice(fromIndex, 1)
  if (moved === undefined) {
    return null
  }
  // Removing the source shifts later indices left by one.
  const adjusted = toIndex > fromIndex ? toIndex - 1 : toIndex
  next.splice(adjusted, 0, moved)
  return next
}
