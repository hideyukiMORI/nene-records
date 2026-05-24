import { describe, expect, it } from 'vitest'
import {
  parsePublicBrowseOffset,
  PUBLIC_BROWSE_PAGE_SIZE,
} from '@/features/public-browse-entity-records/lib/public-browse-pagination'

describe('public-browse-pagination', () => {
  it('defaults invalid offset to zero', () => {
    expect(parsePublicBrowseOffset(null)).toBe(0)
    expect(parsePublicBrowseOffset('-1')).toBe(0)
    expect(parsePublicBrowseOffset('abc')).toBe(0)
  })

  it('parses valid integer offset', () => {
    expect(parsePublicBrowseOffset('20')).toBe(20)
  })

  it('exposes page size constant aligned with entity list default', () => {
    expect(PUBLIC_BROWSE_PAGE_SIZE).toBe(20)
  })
})
