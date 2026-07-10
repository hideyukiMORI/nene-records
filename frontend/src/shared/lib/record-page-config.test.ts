import { describe, expect, it } from 'vitest'
import {
  DEFAULT_RECORD_PAGE_CONFIG,
  parseRecordPageConfig,
  serializeRecordPageConfig,
} from './record-page-config'

describe('parseRecordPageConfig', () => {
  it('returns the default (both visible) for empty / missing / broken JSON', () => {
    expect(parseRecordPageConfig(undefined)).toEqual(DEFAULT_RECORD_PAGE_CONFIG)
    expect(parseRecordPageConfig(null)).toEqual(DEFAULT_RECORD_PAGE_CONFIG)
    expect(parseRecordPageConfig('')).toEqual(DEFAULT_RECORD_PAGE_CONFIG)
    expect(parseRecordPageConfig('not json')).toEqual(DEFAULT_RECORD_PAGE_CONFIG)
    expect(parseRecordPageConfig('[]')).toEqual(DEFAULT_RECORD_PAGE_CONFIG)
  })

  it('defaults each flag to true and honours explicit false', () => {
    expect(parseRecordPageConfig('{}')).toEqual({ comments: true, related: true })
    expect(parseRecordPageConfig('{"comments":false}')).toEqual({ comments: false, related: true })
    expect(parseRecordPageConfig('{"related":false}')).toEqual({ comments: true, related: false })
  })

  it('treats non-boolean values as true (visible)', () => {
    expect(parseRecordPageConfig('{"comments":"no","related":0}')).toEqual({
      comments: true,
      related: true,
    })
  })

  it('round-trips through serializeRecordPageConfig', () => {
    const config = { comments: false, related: true }
    expect(parseRecordPageConfig(serializeRecordPageConfig(config))).toEqual(config)
  })
})
