import { afterEach, describe, expect, it } from 'vitest'
import { getBasePath, normalizeBasePath, withBasePath } from './base-path'

function setBaseHref(href: string | null): void {
  document.querySelector('base')?.remove()
  if (href !== null) {
    const base = document.createElement('base')
    base.setAttribute('href', href)
    document.head.appendChild(base)
  }
}

afterEach(() => {
  setBaseHref(null)
})

describe('normalizeBasePath', () => {
  it('treats empty / slash as root', () => {
    expect(normalizeBasePath('')).toBe('')
    expect(normalizeBasePath('/')).toBe('')
  })

  it('normalizes to a leading-slash, no-trailing-slash segment', () => {
    expect(normalizeBasePath('blog')).toBe('/blog')
    expect(normalizeBasePath('/blog/')).toBe('/blog')
    expect(normalizeBasePath('a/b')).toBe('/a/b')
  })
})

describe('getBasePath', () => {
  it('is root when <base> is absent or points at /', () => {
    setBaseHref(null)
    expect(getBasePath()).toBe('')
    setBaseHref('/')
    expect(getBasePath()).toBe('')
  })

  it('derives the base from <base href>', () => {
    setBaseHref('/blog/')
    expect(getBasePath()).toBe('/blog')
  })
})

describe('withBasePath', () => {
  it('is a no-op at root', () => {
    setBaseHref('/')
    expect(withBasePath('/api/v1/x')).toBe('/api/v1/x')
  })

  it('prefixes under a sub-directory', () => {
    setBaseHref('/blog/')
    expect(withBasePath('/api/v1/x')).toBe('/blog/api/v1/x')
    expect(withBasePath('/login')).toBe('/blog/login')
  })
})
