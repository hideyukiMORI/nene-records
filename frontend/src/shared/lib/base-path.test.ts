import { afterEach, describe, expect, it } from 'vitest'
import { getBasePath, normalizeBasePath, withBasePath } from './base-path'

afterEach(() => {
  delete window.__BASE_PATH__
})

describe('normalizeBasePath', () => {
  it('treats empty / slash as root', () => {
    expect(normalizeBasePath('')).toBe('')
    expect(normalizeBasePath('/')).toBe('')
    expect(normalizeBasePath('  ')).toBe('')
  })

  it('normalizes to a leading-slash, no-trailing-slash segment', () => {
    expect(normalizeBasePath('blog')).toBe('/blog')
    expect(normalizeBasePath('/blog')).toBe('/blog')
    expect(normalizeBasePath('/blog/')).toBe('/blog')
    expect(normalizeBasePath('a/b')).toBe('/a/b')
  })
})

describe('getBasePath', () => {
  it('is root when the global is unset', () => {
    expect(getBasePath()).toBe('')
  })

  it('reads and normalizes window.__BASE_PATH__', () => {
    window.__BASE_PATH__ = '/blog/'
    expect(getBasePath()).toBe('/blog')
  })
})

describe('withBasePath', () => {
  it('is a no-op at root', () => {
    expect(withBasePath('/api/v1/x')).toBe('/api/v1/x')
  })

  it('prefixes under a sub-directory', () => {
    window.__BASE_PATH__ = '/blog'
    expect(withBasePath('/api/v1/x')).toBe('/blog/api/v1/x')
    expect(withBasePath('/login')).toBe('/blog/login')
  })
})
