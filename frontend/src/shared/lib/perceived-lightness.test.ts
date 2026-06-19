import { describe, expect, it } from 'vitest'
import { inkOn, perceivedLightness } from './perceived-lightness'

describe('perceivedLightness', () => {
  it('reads hex (long and short)', () => {
    expect(perceivedLightness('#ffffff')).toBe(1)
    expect(perceivedLightness('#000000')).toBe(0)
    expect(perceivedLightness('#fff')).toBe(1)
    expect(perceivedLightness('#0f1115')).toBeLessThan(0.1)
    expect(perceivedLightness('#f7f8fa')).toBeGreaterThan(0.8)
  })

  it('reads oklch / oklab lightness (first component)', () => {
    expect(perceivedLightness('oklch(0.98 0.01 90)')).toBeCloseTo(0.98, 2)
    expect(perceivedLightness('oklch(0.2 0.05 250)')).toBeCloseTo(0.2, 2)
    expect(perceivedLightness('oklch(95% 0.02 120)')).toBeCloseTo(0.95, 2)
  })

  it('reads rgb / hsl', () => {
    expect(perceivedLightness('rgb(255, 255, 255)')).toBe(1)
    expect(perceivedLightness('rgba(0,0,0,0.5)')).toBe(0)
    expect(perceivedLightness('hsl(220, 30%, 12%)')).toBeCloseTo(0.12, 2)
  })

  it('falls back to light (dark ink) for unparseable values', () => {
    expect(perceivedLightness('var(--whatever)')).toBe(1)
    expect(perceivedLightness('color-mix(in oklch, red, blue)')).toBe(1)
  })
})

describe('inkOn', () => {
  it('returns dark ink on light backgrounds and light ink on dark', () => {
    expect(inkOn('#ffffff')).toBe('#16181d')
    expect(inkOn('#0f1115')).toBe('#f4f5f7')
    expect(inkOn('oklch(0.2 0.05 250)')).toBe('#f4f5f7')
    expect(inkOn('oklch(0.97 0.01 90)')).toBe('#16181d')
  })
})
