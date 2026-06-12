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
})
