import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { validateThemeCss } from './validate-theme-css'

// vitest runs with cwd at the frontend project root.
const consumerCss = readFileSync(
  resolve(process.cwd(), 'src/shared/ui/theme/themes/consumer-brand.css'),
  'utf8',
)
const auroraCss = readFileSync(
  resolve(process.cwd(), 'src/shared/ui/theme/themes/aurora.css'),
  'utf8',
)
const auroraComponentsCss = readFileSync(
  resolve(process.cwd(), 'src/shared/ui/theme/themes/aurora.components.css'),
  'utf8',
)
const readingCss = readFileSync(
  resolve(process.cwd(), 'src/shared/ui/theme/themes/reading.css'),
  'utf8',
)
const readingComponentsCss = readFileSync(
  resolve(process.cwd(), 'src/shared/ui/theme/themes/reading.components.css'),
  'utf8',
)

describe('validateThemeCss', () => {
  it('accepts the built-in consumer-brand.css (scoped, no external refs)', () => {
    expect(validateThemeCss(consumerCss, { themeId: 'consumer' })).toEqual([])
  })

  it('accepts the built-in aurora theme css (tokens + component overlay, scoped)', () => {
    expect(validateThemeCss(auroraCss, { themeId: 'aurora' })).toEqual([])
    expect(validateThemeCss(auroraComponentsCss, { themeId: 'aurora' })).toEqual([])
  })

  it('accepts the built-in reading theme css (tokens + components, scoped)', () => {
    expect(validateThemeCss(readingCss, { themeId: 'reading' })).toEqual([])
    expect(validateThemeCss(readingComponentsCss, { themeId: 'reading' })).toEqual([])
  })

  it('accepts theme-scoped and .nene-public selectors', () => {
    const css = `
      [data-theme='aurora'] { --color-accent: oklch(60% 0.1 200); }
      [data-theme='aurora-dark'] { --color-accent: oklch(72% 0.1 200); }
      .nene-public .card { border-radius: var(--radius-md); }
    `
    expect(validateThemeCss(css, { themeId: 'aurora' })).toEqual([])
  })

  it('rejects an unscoped / foreign selector', () => {
    const css = `body { background: red; } [data-theme='other'] { --x: 1; }`
    const issues = validateThemeCss(css, { themeId: 'aurora' })
    expect(issues.length).toBe(2)
  })

  it('rejects @import', () => {
    const css = `@import url('https://fonts.example/x.css'); .nene-public { color: red; }`
    expect(validateThemeCss(css, { themeId: 'aurora' }).some((i) => i.path === '@import')).toBe(
      true,
    )
  })

  it('rejects external url()', () => {
    const css = `.nene-public { background-image: url('https://cdn.evil/x.png'); }`
    expect(
      validateThemeCss(css, { themeId: 'aurora' }).some((i) => i.message.includes('external url')),
    ).toBe(true)
  })

  it('allows data URIs and bundle-relative url()', () => {
    const css = `.nene-public { background-image: url('data:image/svg+xml,%3Csvg%3E'); }
      [data-theme='aurora'] .x { background: url(./assets/shape.svg); }`
    expect(validateThemeCss(css, { themeId: 'aurora' })).toEqual([])
  })

  it('rejects executable constructs', () => {
    const css = `.nene-public { width: expression(alert(1)); }`
    expect(validateThemeCss(css, { themeId: 'aurora' }).length).toBeGreaterThan(0)
  })

  it('flags active content inside a data:image/svg+xml URI', () => {
    const css = `.nene-public { background: url("data:image/svg+xml,%3Csvg%20onload%3D%22x()%22%3E%3C/svg%3E"); }`
    expect(
      validateThemeCss(css, { themeId: 'aurora' }).some((i) =>
        i.message.includes('on* event handler'),
      ),
    ).toBe(true)
  })
})
