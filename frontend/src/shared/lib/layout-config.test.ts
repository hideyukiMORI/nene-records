import { describe, expect, it } from 'vitest'
import {
  allActiveSideRegions,
  bodyColumns,
  DEFAULT_LAYOUT_CONFIG,
  migrateLayoutConfig,
} from './layout-config'

describe('migrateLayoutConfig', () => {
  it('returns defaults for null / non-object input', () => {
    expect(migrateLayoutConfig(null)).toEqual(DEFAULT_LAYOUT_CONFIG)
    expect(migrateLayoutConfig('nope')).toEqual(DEFAULT_LAYOUT_CONFIG)
  })

  it('migrates the legacy flat schema into record, defaulting home to 1 column', () => {
    const result = migrateLayoutConfig({ columns: 3, mainPos: 'right', swap: true })
    expect(result.home).toEqual({ columns: 1, mainPos: 'left', swap: false })
    expect(result.record).toEqual({ columns: 3, mainPos: 'right', swap: true })
  })

  it('keeps a valid per-page schema', () => {
    const result = migrateLayoutConfig({
      home: { columns: 2, mainPos: 'left', swap: false },
      record: { columns: 3, mainPos: 'center', swap: true },
    })
    expect(result.home.columns).toBe(2)
    expect(result.record.mainPos).toBe('center')
  })

  it('fills missing pages with their defaults (home=1, record=3)', () => {
    const result = migrateLayoutConfig({ home: { columns: 2 } })
    expect(result.home.columns).toBe(2)
    expect(result.record.columns).toBe(3)
  })

  it('coerces invalid values to safe defaults', () => {
    const result = migrateLayoutConfig({ record: { columns: 9, mainPos: 'sideways' } })
    expect(result.record.columns).toBe(3) // invalid column → record fallback
    expect(result.record.mainPos).toBe('left')
  })
})

describe('bodyColumns', () => {
  it('is main-only at 1 column', () => {
    expect(bodyColumns({ columns: 1, mainPos: 'left', swap: false })).toEqual(['main'])
  })

  it('places main per mainPos at 3 columns', () => {
    expect(bodyColumns({ columns: 3, mainPos: 'left', swap: false })).toEqual([
      'main',
      'sidebar',
      'aside',
    ])
    expect(bodyColumns({ columns: 3, mainPos: 'center', swap: false })).toEqual([
      'sidebar',
      'main',
      'aside',
    ])
    expect(bodyColumns({ columns: 3, mainPos: 'right', swap: true })).toEqual([
      'aside',
      'sidebar',
      'main',
    ])
  })

  it('demotes center to left below 3 columns', () => {
    expect(bodyColumns({ columns: 2, mainPos: 'center', swap: false })).toEqual(['main', 'sidebar'])
  })
})

describe('allActiveSideRegions', () => {
  it('unions side regions across home and record', () => {
    const cfg = {
      home: { columns: 2 as const, mainPos: 'left' as const, swap: false }, // sidebar
      record: { columns: 1 as const, mainPos: 'left' as const, swap: false }, // none
    }
    expect(allActiveSideRegions(cfg).sort()).toEqual(['sidebar'])
  })

  it('is empty when every page is single-column', () => {
    const cfg = {
      home: { columns: 1 as const, mainPos: 'left' as const, swap: false },
      record: { columns: 1 as const, mainPos: 'left' as const, swap: false },
    }
    expect(allActiveSideRegions(cfg)).toEqual([])
  })
})
