import { describe, expect, it } from 'vitest'
import { mediaSrcSet } from './media-derivatives'

describe('mediaSrcSet', () => {
  it('builds src + srcSet for a media image URL', () => {
    const result = mediaSrcSet('/media/2026/06/abc.png')

    expect(result).not.toBeNull()
    expect(result?.src).toBe('/media/md/2026/06/abc.png')
    expect(result?.srcSet).toBe(
      '/media/sm/2026/06/abc.png 320w, /media/md/2026/06/abc.png 640w, /media/lg/2026/06/abc.png 1280w',
    )
  })

  it('preserves an absolute origin prefix', () => {
    const result = mediaSrcSet('https://cdn.example.com/media/2026/06/abc.jpg')

    expect(result?.src).toBe('https://cdn.example.com/media/md/2026/06/abc.jpg')
    expect(result?.srcSet).toContain('https://cdn.example.com/media/lg/2026/06/abc.jpg 1280w')
  })

  it('returns null for non-image media files', () => {
    expect(mediaSrcSet('/media/2026/06/report.pdf')).toBeNull()
  })

  it('returns null for non-media URLs', () => {
    expect(mediaSrcSet('https://example.com/photo.png')).toBeNull()
    expect(mediaSrcSet('/uploads/2026/06/abc.png')).toBeNull()
  })
})
