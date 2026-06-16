import { describe, expect, it } from 'vitest'
import { overrideStyleForTheme, resolveOverrideStyle } from './theme-customization'

describe('resolveOverrideStyle', () => {
  it('emits known CSS variables for valid knob values', () => {
    const style = resolveOverrideStyle({
      accent: '#1e90ff',
      fontBody: 'source-serif',
      contentWidth: 'narrow',
      gutter: 'roomy',
      radius: 'sharp',
    })
    expect(style['--color-accent']).toBe('#1e90ff')
    expect(style['--color-accent-hover']).toContain('color-mix')
    expect(style['--font-sans']).toContain('Source Serif 4')
    expect(style['--content-w']).toBe('960px')
    expect(style['--gutter']).toContain('clamp')
    expect(style['--radius-md']).toBe('2px')
  })

  it('ignores invalid / unknown values (no injection)', () => {
    const style = resolveOverrideStyle({
      accent: 'red; } body { display:none',
      fontBody: 'evil-font',
      contentWidth: '999px',
      radius: 'bogus',
    })
    expect(style).toEqual({})
  })

  it('emits only the provided knobs', () => {
    expect(resolveOverrideStyle({ accent: '#abc' })).toEqual({
      '--color-accent': '#abc',
      '--color-accent-hover': 'color-mix(in oklch, #abc, black 12%)',
    })
  })
})

describe('overrideStyleForTheme', () => {
  const raw = JSON.stringify({ aurora: { contentWidth: 'wide' }, reading: { radius: 'round' } })

  it('returns the resolved style for the requested theme only', () => {
    expect(overrideStyleForTheme(raw, 'aurora')).toEqual({ '--content-w': '1320px' })
    expect(overrideStyleForTheme(raw, 'reading')['--radius-md']).toBe('18px')
  })

  it('returns empty for unknown theme or invalid JSON', () => {
    expect(overrideStyleForTheme(raw, 'consumer')).toEqual({})
    expect(overrideStyleForTheme('not json', 'aurora')).toEqual({})
    expect(overrideStyleForTheme(undefined, 'aurora')).toEqual({})
  })
})
