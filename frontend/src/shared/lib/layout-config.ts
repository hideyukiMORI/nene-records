import type { WidgetRegion } from './resolve-layout'

/**
 * Column configuration for a single public page (the layout builder's
 * "レイアウト構成"). `mainPos: 'center'` is only valid when columns === 3;
 * `swap` flips the two side columns when columns === 3.
 */
export interface PageLayout {
  columns: 1 | 2 | 3
  mainPos: 'left' | 'center' | 'right'
  swap: boolean
}

/** Pages whose column layout is configured independently. */
export type LayoutPageKey = 'home' | 'record'

/**
 * Per-page layout configuration. Top (`home`) and record-detail (`record`) are
 * configured separately so — WordPress-style — a sidebar/aside can sit on the
 * top page too. A new concept (no backend column yet); the builder persists it
 * in localStorage until a site-settings backend lands.
 */
export interface LayoutConfig {
  home: PageLayout
  record: PageLayout
}

export const DEFAULT_PAGE_LAYOUT: Record<LayoutPageKey, PageLayout> = {
  home: { columns: 1, mainPos: 'left', swap: false },
  record: { columns: 3, mainPos: 'left', swap: false },
}

export const DEFAULT_LAYOUT_CONFIG: LayoutConfig = {
  home: { ...DEFAULT_PAGE_LAYOUT.home },
  record: { ...DEFAULT_PAGE_LAYOUT.record },
}

const LS_KEY = 'nene_layout_cfg'

/** Body column order (left→right) for a page, including `main`. */
export function bodyColumns(page: PageLayout): readonly (WidgetRegion | 'main')[] {
  const columns = page.columns
  if (columns <= 1) return ['main']
  const sides: WidgetRegion[] =
    columns >= 3 ? (page.swap ? ['aside', 'sidebar'] : ['sidebar', 'aside']) : ['sidebar']
  let mainPos = page.mainPos
  if (columns < 3 && mainPos === 'center') mainPos = 'left' // center is 3-column only
  if (mainPos === 'left') return ['main', ...sides]
  if (mainPos === 'right') return [...sides, 'main']
  return [sides[0], 'main', sides[1]] // center: side | main | side
}

/** Side regions actually rendered by a single page's config (excludes `main`). */
export function activeSideRegions(page: PageLayout): WidgetRegion[] {
  return bodyColumns(page).filter((r): r is WidgetRegion => r !== 'main')
}

/** Union of side regions rendered by *any* page — used to flag inactive widgets. */
export function allActiveSideRegions(cfg: LayoutConfig): WidgetRegion[] {
  const set = new Set<WidgetRegion>()
  ;(['home', 'record'] as const).forEach((p) => {
    activeSideRegions(cfg[p]).forEach((r) => {
      set.add(r)
    })
  })
  return [...set]
}

function migrateOne(raw: unknown, fallbackColumns: 1 | 2 | 3): PageLayout {
  const p = (raw !== null && typeof raw === 'object' ? raw : {}) as Record<string, unknown>
  const columns =
    p.columns === 1 || p.columns === 2 || p.columns === 3 ? p.columns : fallbackColumns
  const mainPos =
    p.mainPos === 'left' || p.mainPos === 'center' || p.mainPos === 'right' ? p.mainPos : 'left'
  return { columns, mainPos, swap: p.swap === true }
}

/**
 * Coerce arbitrary stored JSON into a valid per-page config. Migrates the legacy
 * flat schema (`{columns,mainPos,swap}` = record-detail only) into `record`,
 * defaulting `home` to a single column.
 */
export function migrateLayoutConfig(raw: unknown): LayoutConfig {
  if (raw === null || typeof raw !== 'object') {
    return { home: { ...DEFAULT_PAGE_LAYOUT.home }, record: { ...DEFAULT_PAGE_LAYOUT.record } }
  }
  const p = raw as Record<string, unknown>
  // New per-page schema.
  if ('home' in p || 'record' in p) {
    return {
      home: migrateOne(p.home, 1),
      record: migrateOne(p.record, 3),
    }
  }
  // Legacy flat schema → record; home defaults to 1 column.
  if ('columns' in p || 'mainPos' in p || 'swap' in p) {
    return {
      home: { columns: 1, mainPos: 'left', swap: false },
      record: migrateOne(p, 3),
    }
  }
  return { home: { ...DEFAULT_PAGE_LAYOUT.home }, record: { ...DEFAULT_PAGE_LAYOUT.record } }
}

export function loadLayoutConfig(): LayoutConfig {
  try {
    const raw = localStorage.getItem(LS_KEY)
    if (raw === null) return migrateLayoutConfig(null)
    return migrateLayoutConfig(JSON.parse(raw))
  } catch {
    return migrateLayoutConfig(null)
  }
}

export function saveLayoutConfig(cfg: LayoutConfig): void {
  try {
    localStorage.setItem(LS_KEY, JSON.stringify(cfg))
  } catch {
    // ignore quota / unavailable storage
  }
}
