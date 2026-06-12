import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, render } from '@testing-library/react'
import { SandboxedBundle } from './SandboxedBundle'

describe('SandboxedBundle', () => {
  afterEach(() => {
    cleanup()
  })

  it('renders the bundle in a sandboxed iframe without allow-same-origin', () => {
    const { container } = render(<SandboxedBundle html={'<h1>Hi</h1><script>1</script>'} />)
    const iframe = container.querySelector('iframe')

    expect(iframe).not.toBeNull()
    const sandbox = iframe?.getAttribute('sandbox') ?? ''
    expect(sandbox).toContain('allow-scripts')
    // Critical: must NOT grant same-origin, or the frame could reach the parent.
    expect(sandbox).not.toContain('allow-same-origin')
  })

  it('passes the raw html through srcdoc (no sanitization) and injects the height reporter', () => {
    const html = '<h1>Hi</h1><script>window.x=1</script>'
    const { container } = render(<SandboxedBundle html={html} />)
    const srcdoc = container.querySelector('iframe')?.getAttribute('srcdoc') ?? ''
    expect(srcdoc).toContain(html)
    expect(srcdoc).toContain('nene:bundle-height')
  })

  it('renders nothing for empty input', () => {
    const { container } = render(<SandboxedBundle html="   " />)
    expect(container).toBeEmptyDOMElement()
  })
})
