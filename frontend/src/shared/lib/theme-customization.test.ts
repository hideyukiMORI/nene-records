import { describe, expect, it } from 'vitest'
import {
  buildOverrideCss,
  flagAttrsForTheme,
  overrideCssForTheme,
  resolveFlagAttrs,
  resolveDraftImageUrls,
  resolveModeColors,
  resolveModeImages,
  resolveOverrideStyle,
} from './theme-customization'

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

  it('emits display and mono font variables for valid keys', () => {
    const style = resolveOverrideStyle({ fontDisplay: 'playfair', fontMono: 'space-mono' })
    expect(style['--font-display']).toContain('Playfair Display')
    expect(style['--font-mono']).toContain('Space Mono')
  })

  it('ignores unknown display / mono font keys', () => {
    const style = resolveOverrideStyle({ fontDisplay: 'evil-font', fontMono: 'evil-font' })
    expect(style['--font-display']).toBeUndefined()
    expect(style['--font-mono']).toBeUndefined()
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

  it('derives the text scale from fontSize × typeScale', () => {
    const style = resolveOverrideStyle({ fontSize: 'large', typeScale: 'dramatic' })
    expect(style['--text-body']).toBe('1.1875rem')
    // h3 = base * scale^1 > body
    expect(parseFloat(style['--text-h3'] ?? '')).toBeGreaterThan(
      parseFloat(style['--text-body'] ?? ''),
    )
    // overline = base * scale^-2 < body
    expect(parseFloat(style['--text-overline'] ?? '')).toBeLessThan(
      parseFloat(style['--text-body'] ?? ''),
    )
    // clamp-based hero tokens are NOT overridden
    expect(style['--text-display']).toBeUndefined()
  })

  it('scales the space ramp from density', () => {
    const style = resolveOverrideStyle({ density: 'compact' })
    expect(style['--space-md']).toBe('0.85rem')
    expect(style['--space-2xl']).toBe('3.4rem')
  })

  it('emits only the provided knobs', () => {
    expect(resolveOverrideStyle({ accent: '#abc' })).toEqual({
      '--color-accent': '#abc',
      '--color-accent-hover': 'color-mix(in oklch, #abc, black 12%)',
    })
  })
})

describe('resolveModeColors', () => {
  it('derives the surface family + muted text per mode (validated hex only)', () => {
    const light = resolveModeColors(
      { surface: { light: '#ffffff', dark: '#101010' }, text: { light: '#222222' } },
      'light',
    )
    expect(light['--color-surface']).toBe('#ffffff')
    expect(light['--color-surface-raised']).toContain('color-mix')
    expect(light['--color-text-primary']).toBe('#222222')
    expect(light['--color-text-muted']).toContain('transparent')

    const dark = resolveModeColors({ surface: { light: '#fff', dark: '#101010' } }, 'dark')
    expect(dark['--color-surface']).toBe('#101010')
    // no text override for dark → not emitted
    expect(dark['--color-text-primary']).toBeUndefined()
  })

  it('ignores invalid hex', () => {
    expect(resolveModeColors({ surface: { light: 'rgb(0,0,0)' } }, 'light')).toEqual({})
  })
})

describe('resolveModeImages', () => {
  it('emits hero/background url() vars for resolved same-origin paths', () => {
    const style = resolveModeImages(
      {
        images: {
          hero: { light: '/media/lg/2026/06/hero.png' },
          background: { light: '/media/lg/2026/06/bg.jpg' },
        },
      },
      'light',
    )
    expect(style['--hero-image']).toBe('url("/media/lg/2026/06/hero.png")')
    expect(style['--site-bg-image']).toBe('url("/media/lg/2026/06/bg.jpg")')
  })

  it('skips unresolved numeric ids (never emits url(123))', () => {
    expect(resolveModeImages({ images: { hero: { light: 42 } } }, 'light')).toEqual({})
  })

  it('rejects external / protocol-relative / injection attempts', () => {
    for (const evil of [
      '//evil.example/x.png',
      'https://evil.example/x.png',
      'data:image/png;base64,AAAA',
      'javascript:alert(1)',
      '/media/x.png") ; background: url(evil',
      '/media/a b.png',
    ]) {
      expect(resolveModeImages({ images: { hero: { light: evil } } }, 'light')).toEqual({})
    }
  })

  it('reads the requested mode only', () => {
    const overrides = { images: { hero: { light: '/media/lg/l.png', dark: '/media/lg/d.png' } } }
    expect(resolveModeImages(overrides, 'light')['--hero-image']).toBe('url("/media/lg/l.png")')
    expect(resolveModeImages(overrides, 'dark')['--hero-image']).toBe('url("/media/lg/d.png")')
  })
})

describe('resolveDraftImageUrls', () => {
  const idToUrl = (id: number): string | undefined =>
    ({ 2: '/media/2026/06/hero.png', 3: '/media/2026/06/logo.png' })[id]

  it('resolves hero/background ids to the lg derivative and logo to the raw url', () => {
    const out = resolveDraftImageUrls(
      { images: { hero: { light: 2 }, background: { dark: 2 }, logo: { light: 3 } } },
      idToUrl,
    )
    expect(out.images?.hero?.light).toBe('/media/lg/2026/06/hero.png')
    expect(out.images?.background?.dark).toBe('/media/lg/2026/06/logo.png'.replace('logo', 'hero'))
    expect(out.images?.logo?.light).toBe('/media/2026/06/logo.png')
  })

  it('drops unresolved ids and empties, and passes through string urls', () => {
    const out = resolveDraftImageUrls(
      { accent: '#fff', images: { hero: { light: 999, dark: '/media/lg/pre.png' } } },
      idToUrl,
    )
    expect(out.images?.hero?.light).toBeUndefined()
    expect(out.images?.hero?.dark).toBe('/media/lg/pre.png')
    expect(out.accent).toBe('#fff')
  })

  it('returns the same object when there are no images', () => {
    const input = { accent: '#fff' }
    expect(resolveDraftImageUrls(input, idToUrl)).toBe(input)
  })
})

describe('buildOverrideCss / overrideCssForTheme', () => {
  it('scopes mode-agnostic vars to both modes and colours per mode', () => {
    const css = buildOverrideCss(
      { contentWidth: 'wide', surface: { light: '#ffffff', dark: '#101010' } },
      'aurora',
    )
    expect(css).toContain(".nene-public[data-theme='aurora']")
    expect(css).toContain(".nene-public[data-theme='aurora-dark']")
    expect(css).toContain('--content-w: 1320px;')
    expect(css).toContain('--color-surface: #ffffff;')
    expect(css).toContain('--color-surface: #101010;')
  })

  it('places per-mode hero images in the matching mode block', () => {
    const css = buildOverrideCss(
      { images: { hero: { light: '/media/lg/l.png', dark: '/media/lg/d.png' } } },
      'aurora',
    )
    const darkBlock = css.slice(css.indexOf(".nene-public[data-theme='aurora-dark']"))
    const lightBlock = css.slice(0, css.indexOf(".nene-public[data-theme='aurora-dark']"))
    expect(lightBlock).toContain('--hero-image: url("/media/lg/l.png");')
    expect(darkBlock).toContain('--hero-image: url("/media/lg/d.png");')
  })

  it('returns the css for the requested theme only, empty otherwise', () => {
    const raw = JSON.stringify({ aurora: { radius: 'round' } })
    expect(overrideCssForTheme(raw, 'aurora')).toContain('--radius-md')
    expect(overrideCssForTheme(raw, 'consumer')).toBe('')
    expect(overrideCssForTheme('not json', 'aurora')).toBe('')
  })
})

describe('resolveFlagAttrs / flagAttrsForTheme', () => {
  it('maps valid flags to data-* attributes; overrides win over defaults', () => {
    const attrs = resolveFlagAttrs(
      { feedLayout: 'grid' },
      { feedLayout: 'list', cardStyle: 'bordered' },
    )
    expect(attrs).toEqual({ 'data-feed': 'list', 'data-cards': 'bordered' })
  })

  it('drops unknown flags / invalid values', () => {
    expect(resolveFlagAttrs(undefined, { feedLayout: 'spaceship', cardStyle: 'flat' })).toEqual({
      'data-cards': 'flat',
    })
  })

  it('reads flags from stored overrides JSON for the theme', () => {
    const raw = JSON.stringify({ reading: { flags: { feedLayout: 'list' } } })
    expect(flagAttrsForTheme(raw, 'reading')).toEqual({ 'data-feed': 'list' })
    expect(flagAttrsForTheme(raw, 'aurora')).toEqual({})
  })

  it('maps header element visibility flags', () => {
    expect(
      resolveFlagAttrs(undefined, {
        headerSearch: 'hide',
        headerTheme: 'hide',
        headerTagline: 'show',
      }),
    ).toEqual({
      'data-header-search': 'hide',
      'data-header-theme': 'hide',
      'data-header-tagline': 'show',
    })
    // Invalid values are dropped like any other flag.
    expect(resolveFlagAttrs(undefined, { headerSearch: 'collapsed' })).toEqual({})
  })

  it('maps header layout skeleton + modifiers', () => {
    expect(
      resolveFlagAttrs(undefined, {
        headerLayout: 'classic',
        headerNavAlign: 'center',
        headerDensity: 'compact',
      }),
    ).toEqual({
      'data-header': 'classic',
      'data-header-nav': 'center',
      'data-header-density': 'compact',
    })
    expect(resolveFlagAttrs(undefined, { headerLayout: 'two-row' })).toEqual({})
  })

  it('maps centered layout + width/sticky modifiers', () => {
    expect(
      resolveFlagAttrs(undefined, {
        headerLayout: 'centered',
        headerWidth: 'full',
        headerSticky: 'none',
      }),
    ).toEqual({
      'data-header': 'centered',
      'data-header-width': 'full',
      'data-header-sticky': 'none',
    })
  })

  it('maps the motion-reveal capability flag', () => {
    expect(resolveFlagAttrs(undefined, { motionReveal: 'subtle' })).toEqual({
      'data-motion-reveal': 'subtle',
    })
    // Invalid values are dropped like any other flag.
    expect(resolveFlagAttrs(undefined, { motionReveal: 'fancy' })).toEqual({})
  })

  it('maps the motion-header capability flag', () => {
    expect(resolveFlagAttrs(undefined, { motionHeader: 'shrink' })).toEqual({
      'data-motion-header': 'shrink',
    })
    expect(resolveFlagAttrs(undefined, { motionHeader: 'wobble' })).toEqual({})
  })

  it('maps the new hero variants (split / gradient)', () => {
    expect(resolveFlagAttrs(undefined, { hero: 'split' })).toEqual({ 'data-hero': 'split' })
    expect(resolveFlagAttrs(undefined, { hero: 'gradient' })).toEqual({ 'data-hero': 'gradient' })
  })
})
