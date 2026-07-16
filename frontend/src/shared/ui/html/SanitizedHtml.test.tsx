import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, render } from '@testing-library/react'
import { SanitizedHtml } from './SanitizedHtml'

describe('SanitizedHtml', () => {
  afterEach(() => {
    cleanup()
  })

  it('keeps safe markup and inline style (CSS)', () => {
    const { container } = render(
      <SanitizedHtml html={'<div class="x" style="color:red">hi</div>'} />,
    )
    const div = container.querySelector('div.x')
    expect(div?.textContent).toBe('hi')
    expect(div?.getAttribute('style')).toContain('color')
  })

  it('strips <script> tags', () => {
    const { container } = render(<SanitizedHtml html={'<p>ok</p><script>alert(1)</script>'} />)
    expect(container.querySelector('script')).toBeNull()
    expect(container.textContent).toContain('ok')
  })

  it('strips inline event handlers', () => {
    const { container } = render(<SanitizedHtml html={'<img src="x" onerror="alert(1)">'} />)
    const img = container.querySelector('img')
    expect(img).not.toBeNull()
    expect(img?.getAttribute('onerror')).toBeNull()
  })

  it('renders nothing for empty input', () => {
    const { container } = render(<SanitizedHtml html="   " />)
    expect(container).toBeEmptyDOMElement()
  })

  it('strips javascript: URLs from links', () => {
    const { container } = render(<SanitizedHtml html={'<a href="javascript:alert(1)">x</a>'} />)
    expect(container.innerHTML.toLowerCase()).not.toContain('javascript:')
  })

  it('forbids global <style> blocks while keeping the rest', () => {
    const { container } = render(
      <SanitizedHtml html={'<style>body{color:red}</style><p>kept</p>'} />,
    )
    expect(container.querySelector('style')).toBeNull()
    expect(container.querySelector('p')?.textContent).toBe('kept')
  })

  it('keeps new-tab target and forces noopener/noreferrer onto it (#939 — SSR parity)', () => {
    const { container } = render(
      <SanitizedHtml html={'<a href="https://example.com" target="_blank">x</a>'} />,
    )
    const a = container.querySelector('a')
    expect(a?.getAttribute('href')).toBe('https://example.com')
    expect(a?.getAttribute('target')).toBe('_blank')
    const rel = (a?.getAttribute('rel') ?? '').split(/\s+/)
    expect(rel).toContain('noopener')
    expect(rel).toContain('noreferrer')
  })

  it('preserves an existing rel while enforcing the tabnabbing guard', () => {
    const { container } = render(
      <SanitizedHtml html={'<a href="https://example.com" target="_blank" rel="me">x</a>'} />,
    )
    const rel = (container.querySelector('a')?.getAttribute('rel') ?? '').split(/\s+/)
    expect(rel).toContain('me')
    expect(rel).toContain('noopener')
    expect(rel).toContain('noreferrer')
  })

  it('leaves targetless links without a forced rel', () => {
    const { container } = render(<SanitizedHtml html={'<a href="/services">x</a>'} />)
    expect(container.querySelector('a')?.getAttribute('rel')).toBeNull()
  })
})
