/**
 * Builds responsive image URLs against the on-demand derivative endpoint
 * (`GET /media/{preset}/{year}/{month}/{filename}`). Given the public URL of a
 * media-library image, returns a `src` (md preset) plus a `srcSet` covering the
 * preset widths, so the browser can pick the right size. Returns null for URLs
 * that are not media-library images (external URLs, non-images), in which case
 * callers should fall back to the raw URL.
 */

const MEDIA_PATH = /^(.*\/media\/)(\d{4})\/(\d{2})\/([^/?#]+)$/
const IMAGE_EXTENSIONS = /\.(png|jpe?g|webp|gif|avif)$/i

/** preset name → intrinsic width descriptor used in srcset */
const PRESETS: ReadonlyArray<readonly [string, number]> = [
  ['sm', 320],
  ['md', 640],
  ['lg', 1280],
]

const FALLBACK_PRESET = 'md'

export interface ResponsiveSources {
  src: string
  srcSet: string
}

export function mediaSrcSet(url: string): ResponsiveSources | null {
  const match = MEDIA_PATH.exec(url)
  if (match === null) {
    return null
  }

  const [, prefix, year, month, filename] = match
  if (
    prefix === undefined ||
    year === undefined ||
    month === undefined ||
    filename === undefined ||
    !IMAGE_EXTENSIONS.test(filename)
  ) {
    return null
  }

  const derivative = (preset: string): string => `${prefix}${preset}/${year}/${month}/${filename}`

  return {
    src: derivative(FALLBACK_PRESET),
    srcSet: PRESETS.map(([preset, width]) => `${derivative(preset)} ${String(width)}w`).join(', '),
  }
}
