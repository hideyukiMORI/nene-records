/**
 * Approximate the perceived lightness (0 = black … 1 = white) of a CSS colour
 * string, so the theme picker can pick a readable ink colour over a swatch
 * without loading the engine or hand-authoring per-theme text colours (#450).
 *
 * Handles the colour forms theme tokens actually use — hex, rgb()/rgba(),
 * hsl()/hsla(), and oklch()/oklab() (whose first component IS perceptual
 * lightness). Anything unparseable falls back to 1 (assume light → dark ink),
 * which is the safe default for the common light-surface case.
 */
export function perceivedLightness(color: string): number {
  const s = color.trim().toLowerCase()

  if (s === 'white' || s === '#fff' || s === '#ffffff') return 1
  if (s === 'black' || s === '#000' || s === '#000000') return 0
  if (s === 'transparent') return 1

  if (s.startsWith('#')) {
    const rgb = parseHex(s)
    return rgb === null ? 1 : srgbLuminance(rgb)
  }

  // oklch(L …) / oklab(L …): the first component is perceptual lightness.
  const okl = /^okl(?:ch|ab)\(\s*([\d.]+)(%?)/.exec(s)
  if (okl !== null) {
    const value = Number.parseFloat(okl[1])
    return clamp01(okl[2] === '%' ? value / 100 : value)
  }

  // hsl(h s l%): lightness is the third component.
  const hsl = /^hsla?\(\s*[\d.]+(?:deg)?[\s,]+[\d.]+%[\s,]+([\d.]+)%/.exec(s)
  if (hsl !== null) return clamp01(Number.parseFloat(hsl[1]) / 100)

  const rgb = /^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)/.exec(s)
  if (rgb !== null) {
    return srgbLuminance([
      Number.parseFloat(rgb[1]),
      Number.parseFloat(rgb[2]),
      Number.parseFloat(rgb[3]),
    ])
  }

  return 1
}

const DARK_INK = '#16181d'
const LIGHT_INK = '#f4f5f7'

/** A readable ink colour to place on top of `background`. */
export function inkOn(background: string): string {
  return perceivedLightness(background) > 0.5 ? DARK_INK : LIGHT_INK
}

function clamp01(n: number): number {
  if (Number.isNaN(n)) return 1
  return Math.min(1, Math.max(0, n))
}

function parseHex(hex: string): [number, number, number] | null {
  let h = hex.slice(1)
  if (h.length === 3 || h.length === 4) {
    h = h
      .split('')
      .map((c) => c + c)
      .join('')
  }
  if (h.length !== 6 && h.length !== 8) return null
  const r = Number.parseInt(h.slice(0, 2), 16)
  const g = Number.parseInt(h.slice(2, 4), 16)
  const b = Number.parseInt(h.slice(4, 6), 16)
  if (Number.isNaN(r) || Number.isNaN(g) || Number.isNaN(b)) return null
  return [r, g, b]
}

/** WCAG relative luminance (0…1) from 0–255 sRGB channels. */
function srgbLuminance([r, g, b]: [number, number, number]): number {
  const lin = (c: number) => {
    const x = c / 255
    return x <= 0.03928 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4
  }
  return clamp01(0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b))
}
