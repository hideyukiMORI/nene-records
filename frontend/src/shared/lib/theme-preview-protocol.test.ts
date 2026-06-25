import { describe, expect, it } from 'vitest'
import { isThemePreviewRequest, THEME_PREVIEW_PARAM } from './theme-preview-protocol'

describe('isThemePreviewRequest', () => {
  it('detects the preview flag in the query string', () => {
    expect(isThemePreviewRequest(`?${THEME_PREVIEW_PARAM}=1`)).toBe(true)
    expect(isThemePreviewRequest(`?foo=bar&${THEME_PREVIEW_PARAM}=1`)).toBe(true)
  })

  it('is false without the flag', () => {
    expect(isThemePreviewRequest('')).toBe(false)
    expect(isThemePreviewRequest('?foo=bar')).toBe(false)
  })
})
