import type { WidgetRegion } from './resolve-layout'

/**
 * Record-detail column configuration (the layout builder's "レイアウト構成").
 * `mainPos: 'center'` is only valid when columns === 3; `swap` flips the two
 * side columns when columns === 3. A new concept (no backend column yet); the
 * builder persists it in localStorage until a site-settings backend lands.
 */
export interface LayoutConfig {
  columns: 1 | 2 | 3
  mainPos: 'left' | 'center' | 'right'
  swap: boolean
}

export const DEFAULT_LAYOUT_CONFIG: LayoutConfig = { columns: 3, mainPos: 'left', swap: false }

const LS_KEY = 'nene_layout_cfg'

/** Body column order (left→right) for a record-detail page, including `main`. */
export function bodyColumns(cfg: LayoutConfig): readonly (WidgetRegion | 'main')[] {
  const columns = cfg.columns
  if (columns <= 1) return ['main']
  const sides: WidgetRegion[] =
    columns >= 3 ? (cfg.swap ? ['aside', 'sidebar'] : ['sidebar', 'aside']) : ['sidebar']
  let mainPos = cfg.mainPos
  if (columns < 3 && mainPos === 'center') mainPos = 'left' // center is 3-column only
  if (mainPos === 'left') return ['main', ...sides]
  if (mainPos === 'right') return [...sides, 'main']
  return [sides[0], 'main', sides[1]] // center: side | main | side
}

/** Side regions actually rendered by the current config (excludes `main`). */
export function activeSideRegions(cfg: LayoutConfig): WidgetRegion[] {
  return bodyColumns(cfg).filter((r): r is WidgetRegion => r !== 'main')
}

export function loadLayoutConfig(): LayoutConfig {
  try {
    const raw = localStorage.getItem(LS_KEY)
    if (raw === null) return DEFAULT_LAYOUT_CONFIG
    const parsed = JSON.parse(raw) as Partial<LayoutConfig>
    const columns =
      parsed.columns === 1 || parsed.columns === 2 || parsed.columns === 3 ? parsed.columns : 3
    const mainPos =
      parsed.mainPos === 'left' || parsed.mainPos === 'center' || parsed.mainPos === 'right'
        ? parsed.mainPos
        : 'left'
    return { columns, mainPos, swap: parsed.swap === true }
  } catch {
    return DEFAULT_LAYOUT_CONFIG
  }
}

export function saveLayoutConfig(cfg: LayoutConfig): void {
  try {
    localStorage.setItem(LS_KEY, JSON.stringify(cfg))
  } catch {
    // ignore quota / unavailable storage
  }
}
